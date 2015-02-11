<!doctype html>
<html>
<head>
<?php require_once('../lib/dank/templates/head.php'); ?>
</head>
<body>
<div class="grid-container">
<?php require_once('../lib/dank/templates/header.php'); ?>

<div class="section group">
	<div class="col s12">
		<h2>sign up</h2>
		<form action="/register/process/" method="post">
		<table>
		<tr><td>Your Email:</td><td><input tabindex="1" id="start-here" name="e" type="email" placeholder="you@fuck.off" /></td></tr>
		<tr><td>Your Public Username:</td><td><input tabindex="2" name="u" type="text" maxlength="50" placeholder="frigger" /></td></tr>
		<tr><td>Your Password:</td><td><input tabindex="3" name="p1" type="password" /></td></tr>
		<tr><td>Your Password:</td><td><input tabindex="4" name="p2" type="password" /></td></tr>
		<tr><td>Invite Code:</td><td><input tabindex="5" name="i" type="password" /></td></tr>
		<tr><td><input tabindex="6" type="submit" value="sign up &raquo;" /></td><td></td></tr>
		</table>
		</form>
	</div>
</div>

</div>
<?php require_once('../lib/dank/templates/foot.php'); ?>
</body>
</html>