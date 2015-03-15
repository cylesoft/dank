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
	if (isset($_GET['approval-queue'])) {
		$post_filter['approval-queue'] = true;
	}
} else {
	$post_filter['visibility'] = 6;
}

if (isset($_COOKIE['hide_dank_nsfw']) && trim($_COOKIE['hide_dank_nsfw']) == '1') {
	$current_user['show_nsfw'] = false;
} else {
	$current_user['show_nsfw'] = true;
}

// pagination defaults
$pagination = array();
$pagination['num'] = 20;
$pagination['page'] = 1;

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
		} else if (isset($post_filter['approval-queue'])) {
			?><h2>shit that needs approval</h2>
			<p>posts need <?php echo approval_votes_needed(); ?> vote(s) for approval to be public or <?php echo disapproval_votes_needed(); ?> vote(s) for deletion; based on number of users.</p>
			<?php
		}
		?>
		<div class="posts" id="posts">
			<?php
			$posts = fetch_content($post_filter, array(), $pagination);
			// check for an error fetching the posts
			if (isset($posts['error'])) {
				echo '<pre>'.$posts['error'].'</pre>';
			} else {
				if (count($posts) > 0) {
					// ok -- show each post
					foreach ($posts as $post) {
						// render em
						echo render_post($post, $current_user, $single_post_mode);
					}
				} else {
					echo '<p>No posts to show here...</p>';
				}
			} // end posts fetch error check
			?>
		</div>
		<div id="loading-indicator" style="display: none;">LOADING MORE DANKNESS...</div>
	</div>
	<div class="col s4 sidebar">
		
		<?php
		if ($current_user['loggedin']) {
			
			// show approval queue
			$how_many_need_approval = get_num_unapproved_posts();
			if ($how_many_need_approval > 0) {
				?><div class="approval-queue text-box">
					<p><a href="./?approval-queue"><?php echo $how_many_need_approval; ?> posts</a> need approval!</p>
				</div><?php
			}
			
			// show recent actions
			$action_log = get_action_log($current_user['user_id']);
			foreach ($action_log as $log) {
				if ($log['tsc'] < strtotime('7 days ago')) {
					continue;
				}
				if ($log['action_type'] == 'content-rejected') {
					?><div class="text-box log-entry content-rejected">Your content was rejected and deleted. Try harder. (<?php echo relative_timestamp($log['tsc']); ?>)</div><?php
				} else if ($log['action_type'] == 'content-approved') {
					?><div class="text-box log-entry content-approved"><a href="/content/<?php echo $log['post_id_affected']; ?>/">Your content was approved</a>, great job, cool story. (<?php echo relative_timestamp($log['tsc']); ?>)</div><?php
				} else if ($log['action_type'] == 'new-comment') {
					?><div class="text-box log-entry new-comment">Someone posted a new comment on your content. <a href="/content/<?php echo $log['post_id_affected']; ?>/">Check it out.</a> (<?php echo relative_timestamp($log['tsc']); ?>)</div><?php
				}
			}
			
		?>
		<div class="new-post">
			<form id="new-post-form" enctype="multipart/form-data" action="/content/process/" method="post">
			<input type="hidden" name="a" value="n" />
			<input type="hidden" id="max-file-bytes" value="20000000" />
			<p id="file-list"></p>
			<p><textarea name="content" placeholder="post some new shit here, basic markdown accepted"></textarea></p>
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
			<p><form id="hide-nsfw-form" action="./" method="post"><label>Hide NSFW content? <input type="checkbox" value="1" name="hide_nsfw" id="nsfw-hide-toggle" <?php echo (($current_user['show_nsfw']) ? '': 'checked="checked"'); ?> /></label></form></p>
		</div>
		
	</div>
</div>

</div>

<?php require_once('../lib/dank/templates/foot.php'); ?>
</body>
</html>