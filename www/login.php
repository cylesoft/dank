<?php

// login page if they are not already logged in
require_once('../lib/dank/login_check.php');

// otherwise, wtf? 
if (isset($current_user) && isset($current_user['loggedin']) && $current_user['loggedin'] == true) {
	header('Location: /');
	die();
}

?><!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>dankmeme</title>
<link href="//fonts.googleapis.com/css?family=Lato:300italic,700italic|Open+Sans:400italic,700italic,400,700" rel="stylesheet" type="text/css" />
<link href="/css/dank.css" rel="stylesheet" type="text/css" />
</head>
<body onload="document.getElementById('start-here').focus()">
<div class="grid-container">

<div class="section group">
	<div class="col s12">
		<h1>dankmeme</h1>
	</div>
</div>

<div class="section group">
	<div class="col s12">
		<form action="/login/" method="post">
		<p><input tabindex="1" id="start-here" name="e" type="email" placeholder="you@fuck.off" /></p>
		<p><input tabindex="2" name="p" type="password" /></p>
		<p><input tabindex="3" type="submit" value="log in &raquo;" /></p>
		</form>
	</div>
</div>

</div>

<script src="dank.js" type="text/javascript"></script>
</body>
</html>