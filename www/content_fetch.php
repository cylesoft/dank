<?php

// fetch content for infinite scrolling

require_once('../lib/dank/login_check.php');

require_once('../lib/dank/content_controller.php');

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

if (isset($_REQUEST['num']) && is_numeric($_REQUEST['num']) && $_REQUEST['num'] * 1 > 0) {
	$pagination['num'] = (int) $_REQUEST['num'] * 1;
}

if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] * 1 > 1) {
	$pagination['page'] = (int) $_REQUEST['page'] * 1;
}

$posts = fetch_content($post_filter, array(), $pagination);
// check for an error fetching the posts
if (isset($posts['error'])) {
	echo '<pre>'.$posts['error'].'</pre>';
} else {
	if (count($posts) > 0) {
		// ok -- show each post
		foreach ($posts as $post) {
			// render em
			echo render_post($post, $current_user);
		}
	} else {
		//echo '<p>No posts to show here...</p>';
	}
} // end posts fetch error check