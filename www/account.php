<?php

$login_required = true;
require_once('../lib/dank/login_check.php');

$get_user_info = $mysqli->query('SELECT * FROM users WHERE user_id='.$current_user['userid']);
$user_info = $get_user_info->fetch_assoc();

?><!doctype html>
<html>
<?php require_once('../lib/dank/templates/head.php'); ?>
</head>
<body>
<div class="grid-container">
<?php require_once('../lib/dank/templates/header.php'); ?>

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
<?php require_once('../lib/dank/templates/foot.php'); ?>
</body>
</html>