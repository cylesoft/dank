<?php

// dankmeme.

require_once('../lib/dank/login_check.php');

require_once('../lib/dank/content_controller.php');

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

<div class="section group header">
	<div class="col s8">
		<h1>dankmeme</h1>
	</div>
	<div class="col s4">
		<?php
		if ($current_user['loggedin'] == false) {
		?>
		<p>got content? <a href="/login/">log in</a></p>
		<?php
		} else {
		?>
		<p>done here? <a href="/logout/">log out</a></p>
		<?php
		} // end login check
		?>
	</div>
</div>

<?php
if ($current_user['loggedin']) {
?>
<div class="section group">
	<div class="col s12">
		<form enctype="multipart/form-data" action="/content/process/" method="post">
		<input type="hidden" name="a" value="n" />
		<p><textarea name="content" placeholder="post some shit here"></textarea></p>
		<p><input type="file" name="file" /> Max size: <?php echo ini_get('upload_max_filesize'); ?></p>
		<p><label><input type="checkbox" name="public" value="1" checked="checked" /> make post public?</label></p>
		<p><label><input type="checkbox" name="anon" value="1" /> post anonymously?</label></p>
		<p><label><input type="checkbox" name="nsfw" value="1" /> nsfw?</label></p>
		<p><input type="submit" class="small green" value="post that shit &raquo;" /></p>
		</form>
		<hr />
	</div>
</div>
<?php
} // end login check
?>

<div class="section group">
	<div class="col s12">
		<!-- posts -->
		<div class="posts">
			<?php
			$posts = fetch_content();
			foreach ($posts as $post) {
				?>
				<div data-post-id="<?php echo $post['post_id']; ?>" class="post <?php echo $post['post_type']; ?>">
					<!-- <?php echo print_r($post, true); ?> -->
					<?php if ($post['post_type'] == 'image' && isset($post['files'])) { ?>
					<p><img src="<?php echo $post['files'][0]['file_url']; ?>" /></p>
					<?php } ?>
					<?php if ($post['post_type'] == 'audio' && isset($post['files'])) { ?>
					<p><audio controls="controls" src="<?php echo $post['files'][0]['file_url']; ?>"></audio></p>
					<?php } ?>
					<?php if ($post['post_type'] == 'video' && isset($post['files'])) { ?>
					<p><video src="<?php echo $post['files'][0]['file_url']; ?>"></video></p>
					<?php } ?>
					<?php if (isset($post['thetext'])) { ?><p><?php echo $post['thetext']; ?></p><?php } ?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>

</div>

<script src="/js/dank.js" type="text/javascript"></script>
</body>
</html>