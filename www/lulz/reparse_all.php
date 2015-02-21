<?php
	
// go through the database and reparse all text

$login_required = true;
require_once('../../lib/dank/login_check.php');

require_once('../../lib/dank/content_controller.php');

$row_counter = 0;

$get_all_text = $mysqli->query('SELECT post_id, rawtext, thetext FROM posts WHERE rawtext IS NOT NULL ORDER BY post_id ASC');
if (!$get_all_text) {
	die('failure to get text: ' . $mysqli->error);
}
while ($text_row = $get_all_text->fetch_assoc()) {
	$new_text = parse_text($text_row['rawtext']);
	if ($new_text['text'] !== $text_row['thetext']) {
		// update with newer text
		$new_text_db = "'".$mysqli->escape_string($new_text['text'])."'";
		$update_text = $mysqli->query("UPDATE posts SET thetext=$new_text_db WHERE post_id=".$text_row['post_id']);
		if (!$update_text) {
			die('failure to update row: ' . $mysqli->error);
		}
		$row_counter++;
	}
}

echo '<pre>'.$row_counter.' posts updated.</pre>';