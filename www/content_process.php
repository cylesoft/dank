<?php

$login_required = true;
require_once(__DIR__.'/../lib/dank/login_check.php');

if (!isset($_REQUEST['a']) || trim($_REQUEST['a']) == '') {
	die('no action given, dunno what to do');
}

$action = strtolower(trim($_REQUEST['a']));

//echo '<pre>$_REQUEST: '.print_r($_REQUEST, true).'</pre>';
//echo '<pre>$_FILES: '.print_r($_FILES, true).'</pre>';

require_once(__DIR__.'/../lib/dank/content_controller.php');

if ($action == 'n') {
	
	// new content
	
	$the_content = array();
	
	$found_content = false; // right now we'll assume there's no content to post
	
	// is there text content?
	if (isset($_POST['content']) && trim($_POST['content']) != '') {
		$found_content = true;
		$the_content['text'] = trim($_POST['content']);
	}
	
	// is there a file?
	if (isset($_FILES['file']) && isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) {
		$found_content = true;
		$the_content['php_file'] = $_FILES['file'];
	} else {
		// handle file upload error?
	}
	
	// did we find something to post or not?
	if ($found_content == false) {
		die('no content given');
	}
	
	$the_content['user_id'] = $current_user['user_id'];
	
	if (isset($_POST['anon']) && trim($_POST['anon']) == '1') {
		$the_content['anonymous'] = true;
	} else {
		$the_content['anonymous'] = false;
	}
	
	if (isset($_POST['nsfw']) && trim($_POST['nsfw']) == '1') {
		$the_content['nsfw'] = true;
	} else {
		$the_content['nsfw'] = false;
	}
	
	if (isset($_POST['public']) && trim($_POST['public']) == '1') {
		$the_content['visibility'] = 5; // requires peer approval for public
	} else {
		$the_content['visibility'] = 3; // members only
	}
	
	$post_result = post_new_content($the_content);
	
	//echo '<pre>post_new_content: '.print_r($post_result, true).'</pre>';
	
	if ($post_result['ok'] == true) {
		header('Location: /');
	} else {
		echo '<pre>post_new_content: '.print_r($post_result, true).'</pre>';
	}
		
} else if ($action == 'e') {
	
	// edit content
	
	if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
		header('HTTP/1.1 400 Bad Request');
		die('no post ID given');
	}
	
	$post_id = (int) $_POST['post_id'] * 1;
	
	$the_content = array();
	$the_content['post_id'] = $post_id;
	
	$found_content = false; // right now we'll assume there's no content to post
	
	// is there text content?
	if (isset($_POST['content']) && trim($_POST['content']) != '') {
		$found_content = true;
		$the_content['text'] = trim($_POST['content']);
	}
	
	// is there a file?
	if (isset($_FILES['file']) && isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) {
		$found_content = true;
		$the_content['php_file'] = $_FILES['file'];
	} else {
		// handle file upload error?
	}
	
	// did we find something to post or not?
	if ($found_content == false) {
		die('no content given');
	}
	
	$the_content['user_id'] = $current_user['user_id'];
	
	if (isset($_POST['anon']) && trim($_POST['anon']) == '1') {
		$the_content['anonymous'] = true;
	} else {
		$the_content['anonymous'] = false;
	}
	
	if (isset($_POST['nsfw']) && trim($_POST['nsfw']) == '1') {
		$the_content['nsfw'] = true;
	} else {
		$the_content['nsfw'] = false;
	}
	
	if (isset($_POST['public']) && trim($_POST['public']) == '1') {
		$the_content['visibility'] = 5; // requires peer approval for public
	} else {
		$the_content['visibility'] = 3; // members only
	}
	
	$edit_result = edit_content($the_content);
	
	//echo '<pre>edit_content: '.print_r($edit_result, true).'</pre>';
	
	if ($edit_result['ok'] == true) {
		header('Location: /content/'.$post_id.'/');
	} else {
		echo '<pre>edit_content: '.print_r($edit_result, true).'</pre>';
	}
	
} else if ($action == 'd') {
	
	if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
		header('HTTP/1.1 400 Bad Request');
		die('no post ID given');
	}
	
	$post_id = (int) $_POST['post_id'] * 1;
		
	// verify this person owns the content.......
	
	$content = fetch_content( array('post_id' => $post_id) );
	
	if ($content[0]['user_id'] != $current_user['user_id']) {
		header('HTTP/1.1 400 Bad Request');
		die('you do not own this content');
	}
	
	// delete content
	$delete_content_result = delete_content($post_id);
	
	if ($delete_content_result['ok'] == true) {
		header('Location: /');
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo '<pre>delete_content: '.print_r($delete_content_result, true).'</pre>';
	}
		
} else if ($action == 'approve') {
	
	// approve content
	
	if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
		header('HTTP/1.1 400 Bad Request');
		die('no post ID given');
	}
	
	$post_id = (int) $_POST['post_id'] * 1;
	
	$approve_result = approve_post($post_id, $current_user);
	
	if ($approve_result['ok'] == true) {
		if ($approve_result['approved']) {
			echo 'approved';
		} else {
			echo 'ok';
		}
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo 'approve_post: '.print_r($approve_result, true);
	}
	
} else if ($action == 'disapprove') {
	
	// approve content
	
	if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
		header('HTTP/1.1 400 Bad Request');
		die('no post ID given');
	}
	
	$post_id = (int) $_POST['post_id'] * 1;
	
	$disapprove_result = disapprove_post($post_id, $current_user);
	
	if ($disapprove_result['ok'] == true) {
		if ($disapprove_result['deleted']) {
			echo 'deleted';
		} else {
			echo 'ok';
		}
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo 'disapprove_post: '.print_r($disapprove_result, true);
	}
	
}