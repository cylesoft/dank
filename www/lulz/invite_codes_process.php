<?php

// deal with invite code operations

$login_required = true;
require_once('../../lib/dank/login_check.php');

if (!isset($_POST['a']) || trim($_POST['a']) == '') {
	die('no action given, dunno what to do');
}

require_once('../../lib/dank/dbconn_mysql.php');

$action = trim($_POST['a']);

if ($action == 'n') {
	
	if (!isset($_POST['e']) || trim($_POST['e']) == '') {
		die('no email address given, please try again');
	}
	
	$email = trim($_POST['e']);
	
	if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
		die('email provided is invalid, please try again');
	}
	
	$email_db = "'".$mysqli->escape_string($email)."'";
	
	// make sure their email address is not already in the invite queue
	$check_if_in_queue = $mysqli->query("SELECT COUNT(code_id) AS thecount FROM invite_codes WHERE theemail=$email_db");
	$if_in_queue_result = $check_if_in_queue->fetch_assoc();
	if ($if_in_queue_result['thecount'] > 0) {
		die('that email address already has an invite');
	}
	
	// make sure their email address is not already in use by a user
	$check_if_user_already = $mysqli->query("SELECT COUNT(user_id) AS thecount FROM users WHERE email=$email_db");
	$if_user_already_result = $check_if_user_already->fetch_assoc();
	if ($if_user_already_result['thecount'] > 0) {
		die('that email address is already in use by a user');
	}
	
	// ok well we must be cool then
	
	// generate new code at random
	$new_code = bin2hex(openssl_random_pseudo_bytes(16));
	$new_code_hash = password_hash($new_code, PASSWORD_DEFAULT);
	$new_code_db = "'".$mysqli->escape_string($new_code_hash)."'";
	
	// insert into db
	$insert_new_code = $mysqli->query("INSERT INTO invite_codes (thecode, theemail, tsc) VALUES ($new_code_db, $email_db, UNIX_TIMESTAMP())");
	if (!$insert_new_code) {
		die('error inserting code into database: '.$mysqli->error);
	}
	
	// email it to them, yay
	$mail_message = 'To whom it may concern,'."\n\n".'Your invite code to dankest.website is: '.$new_code."\n\n".'Register at: https://dankest.website/register/';
	$send_mail = mail($email, 'dankest.website invite code within', $mail_message, 'From: no-reply@dankest.website');
	if ($send_mail == false) {
		die('error sending them the invite code, oh no');
	}
	
	header('Location: invite_codes.php?sent');
	
} else if ($action == 'd') {
	
	if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
		die('ID given is invalid');
	}
	
	$code_id = (int) $_POST['id'] * 1;
	
	// delete only if not used
	$delete_code = $mysqli->query("DELETE FROM invite_codes WHERE beenused=0 AND code_id=$code_id");
	
	header('Location: invite_codes.php?deleted');
	
} else {
	
	die('invalid action, dunno what to do');
	
}