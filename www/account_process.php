<?php

$login_required = true;
require_once('../lib/dank/login_check.php');

if (!isset($_REQUEST['a']) || trim($_REQUEST['a']) == '') {
	die('no action given, dunno what to do');
}

$action = strtolower(trim($_REQUEST['a']));

if ($action == 'e') {
	
	// edit current user info
	
	$login_again = false;
	
	if (isset($_POST['p1']) && trim($_POST['p1']) != '' && isset($_POST['p2']) && trim($_POST['p2']) != '') {
		if (trim($_POST['p1']) != trim($_POST['p2'])) {
			die('passwords do not match, try again');
		}
		$new_user_pwd_hash = password_hash(trim($_POST['p1']), PASSWORD_DEFAULT);
		$password_db = "'".$mysqli->escape_string($new_user_pwd_hash)."'";
		$login_again = true;
	} else {
		$password_db = '';
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
	
	$username_db = "'".$mysqli->escape_string(trim($_POST['u']))."'";
	$email_db = "'".$mysqli->escape_string(trim($_POST['e']))."'";
	
	$update_user = $mysqli->query("UPDATE users SET $password_db username=$username_db, email=$email_db WHERE user_id=".$current_user['user_id']);
	if (!$update_user) {
		die('mysql error updating user: ' . $mysqli->error);
	}
	
	if ($login_again) {
		header('Location: /logout/');
	} else {
		header('Location: /');
	}
	
}