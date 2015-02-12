<?php

// oh fuck, log em out...

$session_cookie_name = 'dank-session';
$session_cookie_domain = 'dankest.website';

if (isset($_COOKIE[$session_cookie_name]) && trim($_COOKIE[$session_cookie_name]) != '') { // user has a session already?
	// delete the saved session token from the database
	require_once('../lib/dank/dbconn_mysql.php');
	$user_session_id = trim($_COOKIE[$session_cookie_name]);
	$user_session_id_db = "'".$mysqli->escape_string($user_session_id)."'";
	$delete_session = $mysqli->query("DELETE FROM user_sessions WHERE session_id=$user_session_id_db");
}

$_COOKIE = array();
unset($_COOKIE);
setcookie($session_cookie_name, '', time() - 3600);
setcookie($session_cookie_name, '', time() - 3600, '/', $session_cookie_domain);

header('Location: /');
die();
