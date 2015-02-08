<!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>dankmeme sign up</title>
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

<script src="/js/dank.js" type="text/javascript"></script>
</body>
</html>