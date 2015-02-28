<?php
	
// go through the database and reparse all text

$login_required = true;
require_once('../../lib/dank/login_check.php');

require_once('../../lib/dank/content_controller.php');

$post_counter = 0;
$comment_counter = 0;

// run through all posts
$get_all_post_text = $mysqli->query('SELECT post_id, rawtext, thetext FROM posts WHERE rawtext IS NOT NULL ORDER BY post_id ASC');
if (!$get_all_post_text) {
	die('failure to get posts text: ' . $mysqli->error);
}
while ($post_text_row = $get_all_post_text->fetch_assoc()) {
	$new_text = parse_text($post_text_row['rawtext']);
	if ($new_text['text'] !== $post_text_row['thetext']) {
		// update with newer text
		$new_text_db = "'".$mysqli->escape_string($new_text['text'])."'";
		$update_post_text = $mysqli->query("UPDATE posts SET thetext=$new_text_db WHERE post_id=".$post_text_row['post_id']);
		if (!$update_post_text) {
			die('failure to update post row: ' . $mysqli->error);
		}
		$post_counter++;
	}
}

// run through all comments
$get_all_comment_text = $mysqli->query('SELECT comment_id, rawcomment, thecomment FROM comments WHERE rawcomment IS NOT NULL ORDER BY comment_id ASC');
if (!$get_all_comment_text) {
	die('failure to get comments text: ' . $mysqli->error);
}
while ($comment_text_row = $get_all_comment_text->fetch_assoc()) {
	$new_text = parse_text($comment_text_row['rawcomment']);
	if ($new_text['text'] !== $comment_text_row['thecomment']) {
		// update with newer text
		$new_text_db = "'".$mysqli->escape_string($new_text['text'])."'";
		$update_comment_text = $mysqli->query("UPDATE comments SET thecomment=$new_text_db WHERE comment_id=".$comment_text_row['comment_id']);
		if (!$update_comment_text) {
			die('failure to update comment row: ' . $mysqli->error);
		}
		$comment_counter++;
	}
}

echo '<pre>'.$post_counter.' posts updated.</pre>';
echo '<pre>'.$comment_counter.' comments updated.</pre>';