<?php

$login_required = true;
require_once('../lib/dank/login_check.php');

if (!isset($_REQUEST['a']) || trim($_REQUEST['a']) == '') {
	die('no action given, dunno what to do');
}

$action = strtolower(trim($_REQUEST['a']));

//echo '<pre>'.print_r($_POST, true).'</pre>';

require_once('../lib/dank/content_controller.php');

if ($action == 'n') {
	// new comment
	
	if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
		header('HTTP/1.1 400 Bad Request');
		die('no post ID given');
	}
	
	if (!isset($_POST['comment']) || trim($_POST['comment']) == '') {
		header('HTTP/1.1 400 Bad Request');
		die('no comment text given');
	}
	
	$the_comment = array();
	$the_comment['post_id'] = (int) $_POST['post_id'] * 1;
	$the_comment['text'] = trim($_POST['comment']);
	$the_comment['user_id'] = $current_user['userid'];
	$post_comment_result = post_new_comment($the_comment);
	
	if ($post_comment_result['ok'] == true) {
		//die('ok');
		//echo render_comment( $post_comment_result['comment_obj'] );
		echo render_comment(fetch_comment($post_comment_result['id']));
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		die($post_comment_result['error']);
	}
	
} else if ($action == 'e') {
	
} else if ($action == 'd') {
	
} else {
	// uhhh..
}
