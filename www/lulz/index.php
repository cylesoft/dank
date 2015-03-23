<?php

// dankmeme admin

$login_required = true;
require_once(__DIR__.'/../../lib/dank/login_check.php');

?><!doctype html>
<html>
<head>
<?php require_once(__DIR__.'/../../lib/dank/templates/head.php'); ?>
</head>
<body>
<div class="grid-container">
<?php require_once(__DIR__.'/../../lib/dank/templates/header.php'); ?>

<div class="section group">
	<div class="col s12">
		<p>admin</p>
		<ul>
			<li><a href="invite_codes.php">invite codes</a></li>
		</ul>
	</div>
</div>

</div>
<?php require_once(__DIR__.'/../../lib/dank/templates/foot.php'); ?>
</body>
</html>