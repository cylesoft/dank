<?php

/*

	SOME DANK LOGIN CHECKING
		cyle gage, 2015

*/

require_once('dbconn_mysql.php');

// set defaults for your user cookie
$current_user = array(
	'loggedin' => false,
	'userid' => 0,
	'userlevel' => 6,
	'show_nsfw' => false,
);

$return_to = '/'; // where are they going after successful login?
$session_cookie_name = 'dank-session';
$session_cookie_domain = 'dankest.website';

function get_userlevel($user_id) {
	global $mysqli;
	$user_id = (int) $user_id * 1;
	$get_user_row = $mysqli->query("SELECT userlevel FROM users WHERE user_id=$user_id");
	if ($get_user_row->num_rows == 1) {
		$user_row = $get_user_row->fetch_assoc();
		return $user_row['userlevel'] * 1;
	} else {
		return 6;
	}
}

function get_username($user_id) {
	global $mysqli;
	$user_id = (int) $user_id * 1;
	$get_user_row = $mysqli->query("SELECT username FROM users WHERE user_id=$user_id");
	if ($get_user_row->num_rows == 1) {
		$user_row = $get_user_row->fetch_assoc();
		return trim($user_row['username']);
	} else {
		return 'unknown user';
	}
}

function update_last_activity($user_id) {
	global $mysqli;
	$user_id = (int) $user_id * 1;
	$update_activity = $mysqli->query("UPDATE users SET last_activity_ts=UNIX_TIMESTAMP() WHERE id=$user_id");
}

function get_key($bit_length = 128){
	$fp = @fopen('/dev/urandom','rb'); // should be /dev/random but it's too slow
	if ($fp !== FALSE) {
		$key = substr(base64_encode(@fread($fp,($bit_length + 7) / 8)), 0, (($bit_length + 5) / 6)  - 2);
		@fclose($fp);
		$key = str_replace(array('+', '/'), array('0', 'X'), $key);
		return $key;
	}
	return null;
}

if (isset($_COOKIE[$session_cookie_name]) && trim($_COOKIE[$session_cookie_name]) != '') { // user has a session already?
	
	$user_session_complete_token = trim($_COOKIE[$session_cookie_name]);
	if (strpos($user_session_complete_token, ':') === false) {
		// invalid token format
		header('Location: /logout/');
		die();
	} else {
		// using the proper session key/secret token system
		$user_session_pieces = explode(':', $user_session_complete_token);
		$user_session_key_db = "'".$mysqli->escape_string($user_session_pieces[0])."'";
		$check_for_session = $mysqli->query("SELECT * FROM user_sessions WHERE session_key=$user_session_key_db AND expires > UNIX_TIMESTAMP()");
		if ($check_for_session->num_rows == 1) {
			// oh snap -- they might have a session if the secret matches!
			$user_session_row = $check_for_session->fetch_assoc();
			// validate secret
			if (crypt($user_session_pieces[1], $user_session_row['session_secret']) != $user_session_row['session_secret']) {
				// something is wrong, try again!
				header('Location: /logout/');
				die();
			}
			// ok, they're cool
			$current_user_id = $user_session_row['user_id'];
			$current_user['loggedin'] = true;
			$current_user['userid'] = $current_user_id;
			$current_user['username'] = get_username($current_user_id);
			$current_user['userlevel'] = get_userlevel($current_user_id);
			$new_session_key_expires = time() + (60*60*24*30);
			setcookie($session_cookie_name, $user_session_complete_token, $new_session_key_expires, '/', $session_cookie_domain);
			$update_session_expiry = $mysqli->query("UPDATE user_sessions SET expires=$new_session_key_expires WHERE session_key=$user_session_key_db AND user_id=$current_user_id");
			update_last_activity($current_user_id);
			if ($_SERVER['PHP_SELF'] == 'login.php') {
				header('Location: '.$return_to);
				die();
			}
		} else {
			// session is expired, make them log in again!
			header('Location: /logout/');
			die();
		}
	}
	
} else if (isset($_POST['e']) && isset($_POST['p'])) { // user is trying to log in?
	
	if (!filter_var(trim($_POST['e']), FILTER_VALIDATE_EMAIL)) {
		die('the email address you put in is invalid, jeez.');
	}
	
	// login flood control
	$has_flood_control_limit = false;
	$attempt_ip_db = "'".$mysqli->escape_string(trim($_SERVER['REMOTE_ADDR']))."'";
	$check_flood_control = $mysqli->query("SELECT * FROM login_flood_control WHERE ipaddr=$attempt_ip_db");
	if ($check_flood_control->num_rows > 0) {
		$has_flood_control_limit = true;
		$flood_control_info = $check_flood_control->fetch_assoc();
		if ($flood_control_info['tsc'] < (time() - 1800)) {
			// it was over half an hour ago -- remove it
			$delete_flood_control = $mysqli->query('DELETE FROM login_flood_control WHERE id='.$flood_control_info['id']);
			$has_flood_control_limit = false;
		} else {
			$update_flood_control = $mysqli->query('UPDATE login_flood_control SET attempts=attempts+1 WHERE id='.$flood_control_info['id']);
			if ($flood_control_info['attempts'] > 20) {
				die('Sorry, you have tried to log in unsuccessfully way too many times. Please try again in a half hour or so.');
			}
		}
	}
	
	$users_email_db = "'".$mysqli->escape_string(trim($_POST['e']))."'";
	
	$check_for_user = $mysqli->query("SELECT * FROM users WHERE email=$users_email_db");
	if ($check_for_user->num_rows == 1) {
		
		$current_user_row = $check_for_user->fetch_assoc();
		
		// check password
		if (password_verify(trim($_POST['p']), $current_user_row['steakonions']) == false) {
			if (!$has_flood_control_limit) {
				$insert_flood_control = $mysqli->query("INSERT INTO login_flood_control (ipaddr, attempts, tsc) VALUES ($attempt_ip_db, 1, UNIX_TIMESTAMP())");
			}
			die('Your password was incorrect, please try again.');
		} else {
			// password checked out, clear flood control, if there was any
			if ($has_flood_control_limit) {
				$delete_flood_control = $mysqli->query('DELETE FROM login_flood_control WHERE id='.$flood_control_info['id']);
			}
		}
		
		// ok, cool
		$current_user['loggedin'] = true;
		$current_user['userid'] = (int) $current_user_row['user_id'] * 1;
		$current_user['username'] = trim($current_user_row['username']);
		$current_user_id = $current_user['userid'];
		$current_user['userlevel'] = get_userlevel($current_user_id);
		update_last_activity($current_user_id);
		
		// set up new session token
		$new_session_key = get_key(256);
		$new_session_secret = get_key(256);
		$new_session_complete_token = $new_session_key.':'.$new_session_secret;
		$new_session_secret_salt = substr(get_key(256), 0, 22); // make a new 22-character salt
		$new_session_secret_hash = crypt($new_session_secret, '$2y$12$' . $new_session_secret_salt);
		$new_session_key_expires = time() + (60*60*24*30);
		setcookie($session_cookie_name, $new_session_complete_token, $new_session_key_expires, '/', $session_cookie_domain);
		
		// write session to database with hashed secret
		$new_session_key_db = "'".$mysqli->escape_string($new_session_key)."'";
		$new_session_secret_hash_db = "'".$mysqli->escape_string($new_session_secret_hash)."'";
		$new_session_row = $mysqli->query("INSERT INTO user_sessions (session_key, session_secret, user_id, expires, ts) VALUES ($new_session_key_db, $new_session_secret_hash_db, $current_user_id, $new_session_key_expires, UNIX_TIMESTAMP())");		
		
		// logged in, cool
		header('Location: '.$return_to);
		die();
	} else {
		if (!$has_flood_control_limit) {
			$insert_flood_control = $mysqli->query("INSERT INTO login_flood_control (ipaddr, attempts, tsc) VALUES ($attempt_ip_db, 1, UNIX_TIMESTAMP())");
		}
		die('Could not find that email address, sorry. Try again, I guess.');
	}
				
} else if (isset($login_required) && $login_required == true) {
	
	header('Location: /login/');
	die();
	
}
