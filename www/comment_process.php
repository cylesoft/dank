<?php

$login_required = true;
require_once(__DIR__.'/../lib/dank/login_check.php');

if (!isset($_REQUEST['a']) || trim($_REQUEST['a']) == '') {
	die('no action given, dunno what to do');
}

$action = strtolower(trim($_REQUEST['a']));

//echo '<pre>'.print_r($_POST, true).'</pre>';

require_once(__DIR__.'/../lib/dank/content_controller.php');

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
	$the_comment['user_id'] = $current_user['user_id'];
	$post_comment_result = post_new_comment($the_comment);
	
	if ($post_comment_result['ok'] == true) {
		//die('ok');
		//echo render_comment( $post_comment_result['comment_obj'] );
		echo render_comment(fetch_comment($post_comment_result['id']), $current_user);
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		die($post_comment_result['error']);
	}
	
} else if ($action == 'e') {
	
	// edit comment
	if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
		header('HTTP/1.1 400 Bad Request');
		die('no comment ID given');
	}
	
	if (!isset($_POST['comment']) || trim($_POST['comment']) == '') {
		header('HTTP/1.1 400 Bad Request');
		die('no comment text given');
	}
	
	$the_comment = array();
	$the_comment['comment_id'] = (int) $_POST['comment_id'] * 1;
	$the_comment['text'] = trim($_POST['comment']);
	$update_comment_result = edit_comment($the_comment);
	
	if ($update_comment_result['ok'] == true) {
		//die('ok');
		//echo render_comment( $post_comment_result['comment_obj'] );
		echo render_comment(fetch_comment($the_comment['comment_id']), $current_user);
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		die($update_comment_result['error']);
	}
	
} else if ($action == 'd') {
	// delete comment
	if (!isset($_POST['comment_id']) || !is_numeric($_POST['comment_id'])) {
		header('HTTP/1.1 400 Bad Request');
		die('no comment ID given');
	}
	
	$comment_id = (int) $_POST['comment_id'] * 1;
	
	// verify this person owns the comment.......
	
	$comment = fetch_comment($comment_id);
	
	if ($comment['user_id'] != $current_user['user_id']) {
		header('HTTP/1.1 400 Bad Request');
		die('your do not own this comment');
	}
	
	$delete_comment_result = delete_comment($comment_id);
	
	if ($delete_comment_result['ok'] == true) {
		echo 'ok';
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo '<pre>delete_comment: '.print_r($delete_comment_result, true).'</pre>';
	}
	
} else {
	// uhhh..
}
