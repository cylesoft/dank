<?php

$login_required = true;
require_once('../lib/dank/login_check.php');

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
		<form id="new-post-form" class="full-page-new-post-form" enctype="multipart/form-data" action="/content/process/" method="post">
		<input type="hidden" name="a" value="n" />
		<input type="hidden" id="max-file-bytes" value="20000000" />
		<p id="file-list"></p>
		<p>Markdown supported in the text area. Links, #hashtags, @mentions will be auto-parsed.</p>
		<p><textarea name="content" placeholder="post some new shit here"></textarea></p>
		<p><input type="file" name="file" id="files" /> Max size: <?php echo ini_get('upload_max_filesize'); ?></p>
		<p id="file-drop-zone">Or drop files here.</p>
		<p><label><input type="checkbox" name="public" value="1" checked="checked" /> make post public?</label></p>
		<p><label><input type="checkbox" name="anon" value="1" /> post anonymously?</label></p>
		<p><label><input type="checkbox" name="nsfw" value="1" /> nsfw?</label></p>
		<p><input type="submit" class="small green" value="post that shit &raquo;" /></p>
		</form>
	</div>
</div>

</div>

<script src="/js/dank.js" type="text/javascript"></script>
</body>
</html>