<?php

// make a new goddamn user

if (!isset($_POST['i']) || trim($_POST['i']) == '') {
	die('you forgot to put in a goddamn invite code, jeez.');
}

if (!isset($_POST['e']) || trim($_POST['e']) == '') {
	die('you forgot to put in a goddamn email address, jeez.');
}

if (!filter_var(trim($_POST['e']), FILTER_VALIDATE_EMAIL)) {
	die('the email address you put in is invalid, jeez.');
}

if (!isset($_POST['u']) || trim($_POST['u']) == '') {
	die('you forgot to put in a goddamn username, jeez.');
}

if (strlen(trim($_POST['u'])) > 50) {
	die('your username is too damn long');
}

if (preg_match('/^[-_~!$a-zA-Z0-9]+$/i', trim($_POST['u'])) == false) {
	die('your username is invalid, please stick to alphanumeric characters');
}

if (!isset($_POST['p1']) || trim($_POST['p1']) == '') {
	die('you forgot to put in your password, jeez.');
}

if (!isset($_POST['p2']) || trim($_POST['p2']) == '') {
	die('you forgot to put in your password again, jeez.');
}

if (trim($_POST['p1']) != trim($_POST['p2'])) {
	die('your passwords do not match, goddamn.');
}

require_once(__DIR__.'/../lib/dank/dbconn_mysql.php');

$new_user_email_db = "'".$mysqli->escape_string(trim($_POST['e']))."'";
$new_username_db = "'".$mysqli->escape_string(trim($_POST['u']))."'";

// check to see if email already in use
$check_for_email = $mysqli->query("SELECT user_id FROM users WHERE email=$new_user_email_db");
if ($check_for_email->num_rows > 0) {
	die('sorry, but that email address appears to already be in use.');
}

// check to see if username already in use
$check_for_username = $mysqli->query("SELECT user_id FROM users WHERE username=$new_username_db");
if ($check_for_username->num_rows > 0) {
	die('sorry, but that username appears to already be in use.');
}

// get their invite info
$get_invite_code = $mysqli->query("SELECT * FROM invite_codes WHERE theemail=$new_user_email_db AND beenused=0");
if ($get_invite_code->num_rows == 0) {
	die('sorry, but there is no invite code for you, or it was already used');
}

$invite_row = $get_invite_code->fetch_assoc();

// compare their invite code with the hash
if (password_verify(trim($_POST['i']), $invite_row['thecode']) == false) {
	die('invalid invite code, brah');
}

// ok, make a new user

$new_user_pwd_hash = password_hash(trim($_POST['p1']), PASSWORD_DEFAULT);
$new_user_pwd_hash_db = "'".$mysqli->escape_string($new_user_pwd_hash)."'";

$new_user_row = $mysqli->query("INSERT INTO users (username, userlevel, email, steakonions, last_activity_ts, tsc) VALUES ($new_username_db, 3, $new_user_email_db, $new_user_pwd_hash_db, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
if (!$new_user_row) {
	die('error creating new user: '.$mysqli->error);
}

// ok invite code used
$update_invite_code = $mysqli->query("UPDATE invite_codes SET beenused=1, tsu=UNIX_TIMESTAMP() WHERE code_id=".$invite_row['code_id']);

header('Location: /login/');