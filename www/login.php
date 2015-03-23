<?php

// login page if they are not already logged in
require_once(__DIR__.'/../lib/dank/login_check.php');

// otherwise, wtf?
if (isset($current_user) && isset($current_user['loggedin']) && $current_user['loggedin'] == true) {
	header('Location: /');
	die();
}

?><!doctype html>
<html>
<head>
<?php require_once(__DIR__.'/../lib/dank/templates/head.php'); ?>
</head>
<body>
<div class="grid-container">
<?php require_once(__DIR__.'/../lib/dank/templates/header.php'); ?>

<div class="section group">
	<div class="col s12">
		<form action="/login/" method="post">
		<p><input tabindex="1" name="e" type="email" placeholder="you@nope.com" /></p>
		<p><input tabindex="2" name="p" type="password" /></p>
		<p><input tabindex="3" type="submit" class="small" value="log in &raquo;" /></p>
		</form>
	</div>
</div>

</div>
<?php require_once(__DIR__.'/../lib/dank/templates/foot.php'); ?>
</body>
</html>