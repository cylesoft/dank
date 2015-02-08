<?php

// dankmeme admin

$login_required = true;
require_once('../../lib/dank/login_check.php');

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
		<h1>dankmeme</h1>
	</div>
</div>

<div class="section group">
	<div class="col s12">
		<!-- posts -->
		<p>admin</p>
		<pre><?php print_r($current_user); ?></pre>
	</div>
</div>

</div>

<script src="dank.js" type="text/javascript"></script>
</body>
</html>