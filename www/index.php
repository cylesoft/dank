<?php

// dank.

require_once('../lib/dank/login_check.php');

require_once('../lib/dank/content_controller.php');

$single_post_mode = false;

$post_filter = array();

if (isset($_GET['cid']) && is_numeric($_GET['cid'])) {
	$post_filter['post_id'] = (int) $_GET['cid'] * 1;
	$single_post_mode = true;
}

if (isset($_GET['tag']) && trim($_GET['tag']) != '' && trim($_GET['tag']) != '/') {
	$tag_filter = trim($_GET['tag']);
	if (substr($tag_filter, -1) == '/') { $tag_filter = substr($tag_filter, 0, -1); }
	$post_filter['tag'] = $tag_filter;
}

if (isset($_GET['u']) && trim($_GET['u']) != '' && trim($_GET['u']) != '/') {
	$user_filter = trim($_GET['u']);
	if (substr($user_filter, -1) == '/') { $user_filter = substr($user_filter, 0, -1); }
	$post_filter['user'] = $user_filter;
}

if ($current_user['loggedin']) {
	$post_filter['visibility'] = $current_user['userlevel'];
} else {
	$post_filter['visibility'] = 6;
}

if (isset($_COOKIE['hide_dank_nsfw']) && trim($_COOKIE['hide_dank_nsfw']) == '1') {
	$post_filter['show_nsfw'] = false;
} else {
	$post_filter['show_nsfw'] = true;
}

?><!doctype html>
<html>
<?php echo '<!-- '.print_r($_GET, true).' -->'; ?>
<?php echo '<!-- '.print_r($post_filter, true).' -->'; ?>
<!--
      _             _                                  
     | |           | |                                 
   __| | __ _ _ __ | | __
  / _` |/ _` | '_ \| |/ /
 | (_| | (_| | | | |   <
  \__,_|\__,_|_| |_|_|\_\

-->
<head>
<?php require_once('../lib/dank/templates/head.php'); ?>
</head>
<body>
<div class="grid-container">

<?php require_once('../lib/dank/templates/header.php'); ?>

<div class="section group">
	<div class="col s8 main-content">
		<!-- posts -->
		<?php
		if (isset($post_filter['tag'])) {
			?><h2>shit tagged #<?php echo $post_filter['tag']; ?></h2><?php
		} else if (isset($post_filter['user'])) {
			?><h2>shit by <?php echo $post_filter['user']; ?></h2><?php
		}
		?>
		<div class="posts">
			<?php
			$posts = fetch_content($post_filter);
			// check for an error fetching the posts
			if (isset($posts['error'])) {
				echo '<pre>'.$posts['error'].'</pre>';
			} else {
				// ok -- show each post
				foreach ($posts as $post) {
					// render em
					echo render_post($post, $current_user, $single_post_mode);
				}
			} // end posts fetch error check
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
			<p><input type="file" name="file" id="files" /> Max size: <?php echo ini_get('upload_max_filesize'); ?>, accepts: jpg, gif, png, mp3, mp4, webm.</p>
			<p id="file-drop-zone">Or drop files here.</p>
			<p><label><input type="checkbox" name="public" value="1" checked="checked" /> make post public? (requires peer approval)</label></p>
			<p><label><input type="checkbox" name="anon" value="1" /> post anonymously?</label></p>
			<p><label><input type="checkbox" name="nsfw" value="1" /> nsfw?</label></p>
			<p><input type="submit" class="small green" value="post that shit &raquo;" /></p>
			<p>or <a href="/content/new/">use the bigger form &raquo;</a></p>
			</form>
		</div>
		
		<div class="user-stuff text-box">
			<ul>
			<li><a href="/account/">change your account crap</a></li>
			</ul>
		</div>
		<?php
		} // end login check
		?>
		
		<div class="about text-box">
			<p><i><b>dankest.website</b></i> is a content sharing community full of the dankest shit on the internet</p>
		</div>
		
		<div class="text-box">
			<p><form id="hide-nsfw-form" action="./" method="post"><label>Hide NSFW content? <input type="checkbox" value="1" name="hide_nsfw" id="nsfw-hide-toggle" <?php echo (($post_filter['show_nsfw']) ? '': 'checked="checked"'); ?> /></label></form></p>
		</div>
		
	</div>
</div>

</div>

<?php require_once('../lib/dank/templates/foot.php'); ?>
</body>
</html>