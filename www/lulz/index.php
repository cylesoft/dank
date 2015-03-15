<?php

// dankmeme admin

$login_required = true;
require_once('../../lib/dank/login_check.php');

?><!doctype html>
<html>
<head>
<?php require_once('../../lib/dank/templates/head.php'); ?>
</head>
<body>
<div class="grid-container">
<?php require_once('../../lib/dank/templates/header.php'); ?>

<div class="section group">
	<div class="col s12">
		<p>admin</p>
		<ul>
			<li><a href="invite_codes.php">invite codes</a></li>
		</ul>
		
		<!-- <?php print_r($current_user); ?> -->
	</div>
</div>

</div>
<?php require_once('../../lib/dank/templates/foot.php'); ?>
</body>
</html>