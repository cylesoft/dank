<?php

//echo '<pre>$_GET: '.print_r($_GET, true).'</pre>';

$login_required = true;
require_once(__DIR__.'/../lib/dank/login_check.php');

require_once(__DIR__.'/../lib/dank/content_controller.php');

$content_id = (int) $_GET['cid'] * 1;

$content_result = fetch_content( array( 'post_id' => $content_id ) );

if (count($content_result) == 0) {
	die('no piece of content with that ID');
}

$content = $content_result[0];

if ($content['user_id'] > 0 && $content['user_id'] != $current_user['user_id']) {
	die('not your content, cannot edit it, sorry');
}

//echo '<!-- '.print_r($content, true).' -->';

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
		<form id="new-post-form" class="full-page-post-form" enctype="multipart/form-data" action="/content/process/" method="post">
		<input type="hidden" name="a" value="e" />
		<input type="hidden" name="post_id" value="<?php echo $content['post_id']; ?>" />
		<input type="hidden" id="max-file-bytes" value="20000000" />
		<p id="file-list"></p>
		<p><a href="http://packetlife.net/media/library/16/Markdown.pdf" target="_blank">Basic Markdown</a> supported in the text area (bold, italics, headers, lists, code). Links, #hashtags, @mentions will be auto-parsed.</p>
		<p><textarea name="content" placeholder="post some new shit here"><?php echo $content['rawtext']; ?></textarea></p>
		<?php if (isset($content['files']) && count($content['files']) > 0) { ?><p><b>There is a file attached; upload a new one below, or leave it alone.</b></p><?php } ?>
		<p><input type="file" name="file" id="files" /> Max size: <?php echo ini_get('upload_max_filesize'); ?>, accepts: jpg, gif, png, mp3, mp4, webm.</p>
		<p id="file-drop-zone">Or drop files here.</p>
		<p><label><input type="checkbox" name="public" value="1" <?php if ($content['visibility'] == 5 || $content['visibility'] == 6) { ?>checked="checked"<?php } ?> /> make post public? (requires peer approval)</label></p>
		<p><label><input type="checkbox" name="anon" value="1" <?php if ($content['user_id'] == 0) { ?>checked="checked"<?php } ?> /> post anonymously?</label></p>
		<p><label><input type="checkbox" name="nsfw" value="1" <?php if ($content['nsfw'] == 1) { ?>checked="checked"<?php } ?> /> nsfw?</label></p>
		<p><input type="submit" class="small green" value="save that shit &raquo;" /></p>
		</form>
	</div>
</div>

</div>
<?php require_once(__DIR__.'/../lib/dank/templates/foot.php'); ?>
</body>
</html>