<?php

// dankmeme.

require_once('../lib/dank/login_check.php');

require_once('../lib/dank/content_controller.php');

$single_post_mode = false;

$post_filter = array();

if (isset($_GET['cid']) && is_numeric($_GET['cid'])) {
	$post_filter['post_id'] = (int) $_GET['cid'] * 1;
	$single_post_mode = true;
}

if ($current_user['loggedin']) {
	$post_filter['visibility'] = $current_user['userlevel'];
} else {
	$post_filter['visibility'] = 6;
}

?><!doctype html>
<html>
<?php echo '<!-- '.print_r($_GET, true).' -->'; ?>
<!--
      _             _                                  
     | |           | |                                 
   __| | __ _ _ __ | | ___ __ ___   ___ _ __ ___   ___ 
  / _` |/ _` | '_ \| |/ / '_ ` _ \ / _ \ '_ ` _ \ / _ \
 | (_| | (_| | | | |   <| | | | | |  __/ | | | | |  __/
  \__,_|\__,_|_| |_|_|\_\_| |_| |_|\___|_| |_| |_|\___|
                                                       
-->
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
	<div class="col s8 text-box">
		<h1><a href="/">dankmeme</a></h1>
	</div>
	<div class="col s4 text-box">
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

<div class="section group">
	<div class="col s8 main-content">
		<!-- posts -->
		<div class="posts">
			<?php
			$posts = fetch_content($post_filter);
			foreach ($posts as $post) {
				?>
				<div data-post-id="<?php echo $post['post_id']; ?>" class="post <?php echo $post['post_type']; ?>">
					<!-- <?php echo print_r($post, true); ?> -->
					<?php
					$poster_username = ((isset($post['username']) && trim($post['username']) != '') ? $post['username'] : 'Anonymous');
					if ($single_post_mode) {
						?><p class="post-info"><?php echo $poster_username; ?> <?php echo date('Y-m-d h:i A', $post['posted_ts']); ?></p><?php
					} else {
						?><p class="post-info"><?php echo $poster_username; ?> <a href="/content/<?php echo $post['post_id']; ?>/">&raquo;</a></p><?php
					}
					?>
					<?php if ($post['post_type'] == 'image' && isset($post['files'])) { ?>
					<p><img src="<?php echo $post['files'][0]['file_url']; ?>" /></p>
					<?php } ?>
					<?php if ($post['post_type'] == 'audio' && isset($post['files'])) { ?>
					<p><audio controls="controls" src="<?php echo $post['files'][0]['file_url']; ?>"></audio></p>
					<?php } ?>
					<?php if ($post['post_type'] == 'video' && isset($post['files'])) { ?>
					<p><video controls="controls" src="<?php echo $post['files'][0]['file_url']; ?>"></video></p>
					<?php } ?>
					<?php if (isset($post['thetext'])) { ?><p><?php echo $post['thetext']; ?></p><?php } ?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<div class="col s4 sidebar">
		
		<?php
		if ($current_user['loggedin']) {
		?>
		<div class="new-post">
			<form id="new-post-form" enctype="multipart/form-data" action="/content/process/" method="post">
			<input type="hidden" name="a" value="n" />
			<input type="hidden" id="max-file-bytes" value="20000000" />
			<p id="file-list"></p>
			<p><textarea name="content" placeholder="post some new shit here"></textarea></p>
			<p><input type="file" name="file" id="files" /> Max size: <?php echo ini_get('upload_max_filesize'); ?></p>
			<p id="file-drop-zone">Or drop files here.</p>
			<p><label><input type="checkbox" name="public" value="1" checked="checked" /> make post public?</label></p>
			<p><label><input type="checkbox" name="anon" value="1" /> post anonymously?</label></p>
			<p><label><input type="checkbox" name="nsfw" value="1" /> nsfw?</label></p>
			<p><input type="submit" class="small green" value="post that shit &raquo;" /></p>
			</form>
		</div>
		<?php
		} // end login check
		?>
		
		<div class="about text-box">
			<p><i><b>dankmeme</b></i> is a content sharing community full of the dankest shit on the internet</p>
		</div>
	</div>
</div>

</div>

<script src="/js/dank.js" type="text/javascript"></script>
</body>
</html>