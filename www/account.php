<?php

$login_required = true;
require_once('../lib/dank/login_check.php');

$get_user_info = $mysqli->query('SELECT * FROM users WHERE user_id='.$current_user['userid']);
$user_info = $get_user_info->fetch_assoc();

?><!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>dankmeme</title>
<link href="//fonts.googleapis.com/css?family=Lato:300italic,700italic|Open+Sans:400italic,700italic,400,700" rel="stylesheet" type="text/css" />
<link href="/css/dank.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div class="grid-container">

<div class="section group">
	<div class="col s12">
		<h1><a href="/">dankmeme</a></h1>
	</div>
</div>

<div class="section group">
	<div class="col s12">
		<form action="/account/process/" method="post">
		<input type="hidden" name="a" value="e" />
		<p>Note: if you change your password, you will have to log in again.</p>
		<table>
		<tr><td>Your Username:</td><td><input name="u" maxlength="50" type="text" value="<?php echo $user_info['username']; ?>" /></td></tr>
		<tr><td>Your Email:</td><td><input name="e" type="email" placeholder="you@fuck.off" value="<?php echo $user_info['email']; ?>" /></td></tr>
		<tr><td>New password:</td><td><input name="p1" type="password" /></td></tr>
		<tr><td>New password, again:</td><td><input name="p2" type="password" /></td></tr>
		<tr><td>Account created:</td><td><?php echo date('Y-m-d h:i A', $user_info['tsc']); ?></td></tr>
		</table>
		<p><input type="submit" class="small" value="save changes &raquo;" /></p>
		</form>
	</div>
</div>

</div>

<script src="/js/dank.js" type="text/javascript"></script>
</body>
</html>