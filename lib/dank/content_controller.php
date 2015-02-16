<?php

/*
	
	controller for
	 - parsing text content
	 - posting new content in the database
	 - fetching lots of content
	 - editing existing content
	 - deleting existing content
	
	functions should return an array:
		{ "ok": true/false, "error": "if there is one" }
	 
*/

$file_path_base = '/var/www/domains/dankest.website/www/files/'; // where to store files
$file_url_base = '/files/';

require_once('dbconn_mysql.php');

// parse text to link-ify links, #hashtags, and @mentions
function parse_text($text) {
	$link_regex = '/\b(https?:\/\/)?(\S+)\.(\S+)\b/i';
	$hashtag_regex = '/\#(\S+)/i';
	$mention_regex = '/\@(\S+)/i';
	$t = $text;
	$links_found = preg_match_all($link_regex, $t, $link_matches);
	//print_r($links_found);
	//print_r($link_matches);
	$hashtags_found = preg_match_all($hashtag_regex, $t, $hashtag_matches);
	//print_r($hashtags_found);
	//print_r($hashtag_matches);
	$mentions_found = preg_match_all($mention_regex, $t, $mention_matches);
	//print_r($mentions_found);
	//print_r($mention_matches);
	$t = preg_replace_callback($link_regex, function($matches) {
		$the_link = $matches[0];
		if (substr($the_link, 0, 4) != 'http') {
			$the_link = 'http://'.$the_link;
		}
		return '<a href="'.$the_link.'">'.$matches[0].'</a>';
	}, $t);
	$t = preg_replace($hashtag_regex, '<a href="/tagged/$1/">$0</a>', $t);
	$t = preg_replace($mention_regex, '<a href="/by/$1/">$0</a>', $t);
	return array('text' => $t, 'links' => $link_matches[0], 'mentions' => $mention_matches[1], 'hashtags' => $hashtag_matches[1]);
}

/*
	
	content system	
	
*/

// deal with saving new content to the database
function post_new_content($content) {
	
	global $mysqli, $file_path_base, $file_url_base;
	
	if (!isset($content['type'])) {
		$content['type'] = 'unknown';
	}
	
	if (isset($content['text'])) {
		
		$content['type'] = 'text';
		
		$rawtext_db = "'".$mysqli->escape_string($content['text'])."'";
		
		// inspect the incoming text for
		// links (also: youtube, vimeo, jpg/gif/png?)
		// tags (#whatever)
		// user mentions (@whoever)
		// and transform them accordingly
		
		$reformatted_result = parse_text($content['text']);
		
		// use the ['links'] and ['mentions'] and ['hashtags'] keys for anything...?
		// check to see if the @mentioned user(s) even exist?
		// notify user(s) of their mention(s)? index them?
		// index the hashtag reference(s) somewhere?
		// check the links to see if they're images/audio/video?
		
		$reformatted_text = $reformatted_result['text'];
		
		$thetext_db = "'".$mysqli->escape_string($reformatted_text)."'";
		
	} else {
		$rawtext_db = 'null';
		$thetext_db = 'null';
	}
	
	if (isset($content['php_file'])) {
		// handle incoming php file
		/*
			[name] => celery.gif
            [type] => image/gif
            [tmp_name] => /tmp/phptwtsMN
            [error] => 0
            [size] => 543317
		*/
		switch ($content['php_file']['type']) {
			case 'image/gif':
			case 'image/png':
			case 'image/jpeg':
			$content['type'] = 'image';
			break;
			case 'video/mp4':
			case 'video/webm':
			$content['type'] = 'video';
			break;
			case 'audio/mp3':
			$content['type'] = 'audio';
			break;
			default:
			unlink($content['php_file']['tmp_name']);
			return array('ok' => false, 'error' => 'you uploaded an unsupported file type');
		}
		$file_extension = strtolower(substr(strrchr($content['php_file']['name'], "."), 1));
		$unique_file_id = uniqid();
		$new_file_name = 'original_'.$unique_file_id.'.'.$file_extension;
		$new_file_path = $file_path_base.$new_file_name;
		$new_file_url = $file_url_base.$new_file_name;
		echo '<p>'.$new_file_name.'</p>';
		echo '<p>'.$new_file_path.'</p>';
		echo '<p>'.$new_file_url.'</p>';
		if (move_uploaded_file($content['php_file']['tmp_name'], $new_file_path)) {
			$new_file_info = array();
			$new_file_info['uniqid'] = $unique_file_id;
			$new_file_info['path'] = $new_file_path;
			$new_file_info['url'] = $new_file_url;
			if ($content['type'] == 'image') {
				$new_file_dimensions = getimagesize($new_file_path);
				$new_file_info['width'] = $new_file_dimensions[0];
				$new_file_info['height'] = $new_file_dimensions[1];
			} else if ($content['type'] == 'audio' || $content['type'] == 'video') {
				/*
				
					add some kind of ffmpeg hook here to get duration?
				
				*/
				$new_file_info['duration'] = 0;
			}
		} else {
			// handle error moving the file around
			return array('ok' => false, 'error' => 'there was an error moving the uploaded file');
		}
	}
	
	if (isset($content['user_id']) && is_numeric($content['user_id'])) {
		$user_id_db = (int) $content['user_id'] * 1;
	} else {
		$user_id_db = 'null';
	}
	
	if (isset($content['anonymous']) && $content['anonymous'] == true) {
		$user_id_db = 'null';
	}
	
	if (isset($content['nsfw']) && $content['nsfw'] == true) {
		$nsfw_db = 1;
	} else {
		$nsfw_db = 0;
	}
	
	if (isset($content['visibility']) && is_numeric($content['visibility'])) {
		$visibility_db = (int) $content['visibility'] * 1;
	} else {
		$visibility_db = 6;
	}
	
	$post_type_db = "'".$mysqli->escape_string($content['type'])."'";
	$now_db = time();
	
	// insert into database
	$insert_post_into_db = $mysqli->query("INSERT INTO posts (post_type, user_id, visibility, thetext, rawtext, nsfw, posted_ts, updated_ts) VALUES ($post_type_db, $user_id_db, $visibility_db, $thetext_db, $rawtext_db, $nsfw_db, $now_db, $now_db)");
	if (!$insert_post_into_db) {
		$return_result = array('ok' => false, 'error' => 'mysql error on new post: '.$mysqli->error);
	} else {
		$new_post_id = $mysqli->insert_id;
		$return_result = array('ok' => true, 'id' => $new_post_id); // so far so good
		// ok deal with file row if there is one to be made
		if (isset($new_file_info) && is_array($new_file_info) && count($new_file_info) > 0) {
			$new_file_uniqid_db = "'".$mysqli->escape_string($new_file_info['uniqid'])."'";
			$new_file_path_db = "'".$mysqli->escape_string($new_file_info['path'])."'";
			$new_file_url_db = "'".$mysqli->escape_string($new_file_info['url'])."'";
			if (isset($new_file_info['width']) && is_numeric($new_file_info['width'])) {
				$new_file_image_width = (int) $new_file_info['width'] * 1;
			} else {
				$new_file_image_width = 'null';
			}
			if (isset($new_file_info['height']) && is_numeric($new_file_info['height'])) {
				$new_file_image_height = (int) $new_file_info['height'] * 1;
			} else {
				$new_file_image_height = 'null';
			}
			if (isset($new_file_info['duration']) && is_numeric($new_file_info['duration'])) {
				$new_file_duration = (int) $new_file_info['duration'] * 1;
			} else {
				$new_file_duration = 'null';
			}
			$insert_file_into_db = $mysqli->query("INSERT INTO files (file_uniqid, post_id, file_path, file_url, image_width, image_height, duration) VALUES ($new_file_uniqid_db, $new_post_id, $new_file_path_db, $new_file_url_db, $new_file_image_width, $new_file_image_height, $new_file_duration)");
			if (!$insert_file_into_db) {
				$return_result = array('ok' => false, 'error' => 'mysql error on file row: '.$mysqli->error);
			}
		}
	}
	
	// return array
	return $return_result;
	
}

// deal with fetching content from the database
function fetch_content($filter = array(), $order = array(), $pagination = array()) {
	global $mysqli;
	
	$content = array(); // will hold content to return
	
	$query_where_clause = '';
	if (count($filter) > 0) {
		
		$query_where_clause .= 'WHERE ';
		
		$query_where_list = '';
		
		if (isset($filter['post_id']) && is_numeric($filter['post_id'])) {
			if (trim($query_where_list) != '') { $query_where_list .= ' AND '; }
			$query_where_list .= 'post_id='.((int) $filter['post_id'] * 1);
		}
		
		if (isset($filter['visibility']) && is_numeric($filter['visibility'])) {
			if (trim($query_where_list) != '') { $query_where_list .= ' AND '; }
			if (isset($filter['approval-queue']) && $filter['approval-queue'] == true) {
				$query_where_list .= 'visibility = 5';
			} else {
				$query_where_list .= 'visibility >= '.((int) $filter['visibility'] * 1);
			}
		}
		
		if (isset($filter['user']) && trim($filter['user']) != '') {
			if (trim($query_where_list) != '') { $query_where_list .= ' AND '; }
			$query_where_list .= 'posts.user_id IN (SELECT user_id FROM users WHERE username=\''.$mysqli->escape_string($filter['user']).'\')';
		}
		
		if (isset($filter['tag']) && trim($filter['tag']) != '') {
			if (trim($query_where_list) != '') { $query_where_list .= ' AND '; }
			$query_where_list .= 'rawtext LIKE \'%#'.$mysqli->escape_string($filter['tag']).'%\'';
		}
		
		if (isset($filter['show_nsfw']) && $filter['show_nsfw'] == false) {
			if (trim($query_where_list) != '') { $query_where_list .= ' AND '; }
			$query_where_list .= 'nsfw=0';
		}
		
		$query_where_clause .= $query_where_list;
		
	}
	
	$get_content_query = 'SELECT posts.*, users.username FROM posts LEFT JOIN users ON users.user_id=posts.user_id '.$query_where_clause.' ORDER BY posted_ts DESC LIMIT 20';
	//echo '<pre>'.$get_content_query.'</pre>';
	$get_content = $mysqli->query($get_content_query);
	if (!$get_content) {
		return array('error' => 'mysql error: '.$mysqli->error);
	}
	while ($content_row = $get_content->fetch_assoc()) {
		if ($content_row['post_type'] == 'image' || $content_row['post_type'] == 'audio' || $content_row['post_type'] == 'video') {
			$files = array();
			$get_files = $mysqli->query('SELECT file_url, image_width, image_height, duration FROM files WHERE post_id='.$content_row['post_id']);
			while ($file_row = $get_files->fetch_assoc()) {
				$files[] = $file_row;
			}
			$content_row['files'] = $files;
		}
		$content[] = $content_row;
	}
	
	return $content;
	
}

// deal with editing a piece of content
function edit_content($content) {
	global $mysqli;
}

// deal with deleting a piece of content
function delete_content($content_id) {
	global $mysqli;
	
	$content_id = (int) $content_id * 1;
	
	$result = array('ok' => false, 'error' => 'unknown');
	
	// delete content row
	$delete_content_row = $mysqli->query("DELETE FROM posts WHERE post_id=$content_id");
	if (!$delete_content_row) {
		$result = array('ok' => false, 'error' => 'database error deleting the post: '.$mysqli->error);
	} else {
		// delete associated comments
		$delete_content_row = $mysqli->query("DELETE FROM comments WHERE post_id=$content_id");
		if (!$delete_content_row) {
			$result = array('ok' => false, 'error' => 'database error deleting the post comments: '.$mysqli->error);
		} else {
			// get files to delete, if any
			$get_files = $mysqli->query("SELECT file_path FROM files WHERE post_id=$content_id");
			while ($file_row = $get_files->fetch_assoc()) {
				if (file_exists($file_row['file_path'])) {
					$delete_file_result = unlink($file_row['file_path']); // trash it.
				}
			}
			// delete the file rows
			$delete_file_rows = $mysqli->query("DELETE FROM files WHERE post_id=$content_id");
			if (!$delete_file_rows) {
				$result = array('ok' => false, 'error' => 'database error deleting the post files: '.$mysqli->error);
			} else {
				$result = array('ok' => true);
			}
		}
	}
	
	return $result;
	
}

// get the number of currently unapproved post
function get_num_unapproved_posts() {
	global $mysqli;
	$get_num = $mysqli->query('SELECT COUNT(post_id) AS count FROM posts WHERE visibility=5');
	$num_result = $get_num->fetch_assoc();
	return $num_result['count'];
}

// deal with approving of a post
function approve_post($post_id, $current_user) {
	global $mysqli;
	$result = array('ok' => false, 'error' => 'unknown');
	$post_id = (int) $post_id * 1;
	// get post, ensure it's visibility = 5 currently
	// ensure the user approving it is not the post's author
	$get_post = $mysqli->query("SELECT user_id, visibility FROM posts WHERE post_id=$post_id");
	if (!$get_post) {
		$result = array('ok' => false, 'error' => 'error with post approval fetch query: ' . $mysqli->error);
	} else if ($get_post->num_rows != 1) {
		$result = array('ok' => false, 'error' => 'no post found with ID '.$post_id);
	} else {
		$post_info = $get_post->fetch_assoc();
		if ($post_info['visibility'] != 5) {
			$result = array('ok' => false, 'error' => 'post is not pending approval');
		} else if ($post_info['user_id'] == $current_user['userid']) {
			$result = array('ok' => false, 'error' => 'you cannot approve your own post');
		} else {
			// we're good, approve it
			$approve_the_post = $mysqli->query("UPDATE posts SET visibility=6 WHERE post_id=$post_id");
			if (!$approve_the_post) {
				$result = array('ok' => false, 'error' => 'error with post approval update query: ' . $mysqli->error);
			} else {
				$result = array('ok' => true);
			}
		}
	}
	return $result;
}

// render the post bit
function render_post($post, $current_user, $single_post_mode = false) {
	$render = ''; // we start blank
	$render .= '<div class="post-wrap">'; // start post-wrap
	$post_classes = array();
	$post_classes[] = 'post';
	$post_classes[] = $post['post_type'];
	if (isset($post['nsfw']) && $post['nsfw'] == true) {
		$post_classes[] = 'nsfw';
	}
	switch ($post['visibility']) {
		case 1:
		case 2:
		case 3:
		$post_classes[] = 'members-only';
		break;
		case 4:
		case 5:
		$post_classes[] = 'peer-approval';
		break;
		case 6:
		$post_classes[] = 'public';
		break;
		default:
		$post_classes[] = 'unknown-visibility';
	}
	// start actual post content div
	$render .= '<div data-post-id="'.$post['post_id'].'" class="'.implode(' ', $post_classes).'">';
	//$render .= '<!-- '.print_r($post, true).'-->'; // some debug info
	// show the username based on whether anon or not
	$poster_username = ((isset($post['username']) && trim($post['username']) != '') ? '<a href="/by/'.$post['username'].'">'.$post['username'].'</a>' : 'Anonymous');
	// switch for single post mode, show timestamp if true
	$status_label = '';
	if (in_array('members-only', $post_classes)) {
		$status_label .= '<span class="label members-only">Members-only</span> ';
	} else if (in_array('peer-approval', $post_classes)) {
		$status_label .= '<span class="label peer-approval">Requires approval</span> ';
	}
	if (in_array('nsfw', $post_classes)) {
		if ($status_label == '') { $status_label .= ' '; }
		$status_label .= '<span class="label nsfw">NSFW</span> ';
	}
	if ($single_post_mode) {
		$render .= '<p class="post-info">'.$status_label.' '.$poster_username.' '.date('Y-m-d h:i A', $post['posted_ts']).'</p>';
	} else {
		// permalink to this post
		$render .= '<p class="post-info">'.$status_label.' '.$poster_username.' <a href="/content/'.$post['post_id'].'/">&raquo;</a></p>';
	}
	// post content itself, whether image or audio or video
	if ($post['post_type'] == 'image' && isset($post['files'])) {
		$render .= '<p class="post-content"><a href="'.$post['files'][0]['file_url'].'"><img src="'.$post['files'][0]['file_url'].'" /></a></p>';
	} else if ($post['post_type'] == 'audio' && isset($post['files'])) {
		$render .= '<p class="post-content"><audio controls="controls" src="'.$post['files'][0]['file_url'].'">Looks like your browser doesn\'t support this HTML5 audio. Use Chrome.</audio></p>';
	} else if ($post['post_type'] == 'video' && isset($post['files'])) {
		$render .= '<p class="post-content"><video controls="controls" loop="loop" src="'.$post['files'][0]['file_url'].'">Looks like your browser doesn\'t support this HTML5 video. Use Chrome.</video></p>';
	}
	// show text, if any was included
	if (isset($post['thetext']) && trim($post['thetext']) != '') {
		$render .= '<p>'.$post['thetext'].'</p>';
	}
	if (in_array('peer-approval', $post_classes) && $current_user['loggedin'] && $current_user['userid'] != $post['user_id']) {
		$render .= '<p class="approval-form">Approve post? It will become public. <input type="button" value="&#10004; story checks out" class="button green small approve-post" data-post-id="'.$post['post_id'].'" /></p>';
	}
	$render .= '</div>'; // end of actual post div
	// get comments for this post
	$comments = fetch_comments_for_post($post['post_id']);
	if ($current_user['loggedin'] || count($comments) > 0) {
		// there are comments, or they're logged in, either way show the comments block
		$render .= '<div class="comments">';
		// list actual comments
		$render .= '<div class="comments-list" data-post-id="'.$post['post_id'].'">';
		foreach ($comments as $comment) {
			$render .= render_comment($comment); // render each comment bit
		}
		$render .= '</div>'; // end comments list div
		// if logged in, allow new comments
		if ($current_user['loggedin']) {
			$render .= '<div class="new-comment-form '.((count($comments) > 0) ? 'not-alone' : '').'">';
			$render .= '<input type="hidden" value="'.$post['post_id'].'" /> <input type="text" class="your-comment" placeholder="Insert your comment here..." /> <input type="button" class="small post-comment-btn" value="Post &raquo;" />';
			$render .= '</div>';
		}
		$render .= '</div>'; // end comments div
	} // end comments check
	$render .= '</div>'."\n"; // end post-wrap div
	// show tools?
	if ($single_post_mode && $current_user['loggedin']) {
		$render .= '<div class="post-tools">';
		$render .= '<a href="/content/edit/'.$post['post_id'].'/" class="button blue">Edit &raquo;</a> ';
		$render .= '<form style="display: inline;" onsubmit="return confirm(\'You sure you wanna do that?\')" action="/content/process/" method="post"><input type="hidden" name="a" value="d" /><input type="hidden" name="post_id" value="'.$post['post_id'].'" /> <input type="submit" class="button red" value="Delete &raquo;" /></form>';
		$render .= '</div>';
	}
	return $render; // spit it out
}

/*
	
	comment system	
	
*/

// deal with fetching comments for a post
function fetch_comments_for_post($post_id) {
	global $mysqli;
	$post_id = (int) $post_id * 1;
	$comments = array(); // will hold comments to be given back
	$get_comments = $mysqli->query('SELECT comments.comment_id, comments.thecomment, comments.posted_ts, comments.updated_ts, users.username FROM comments LEFT JOIN users ON users.user_id=comments.user_id WHERE post_id='.$post_id.' ORDER BY comment_id ASC');
	if ($get_comments->num_rows > 0) {
		while ($comment = $get_comments->fetch_assoc()) {
			$comments[] = $comment;
		}
	}
	return $comments;
}

// deal with fetching a specific comment
function fetch_comment($comment_id) {
	global $mysqli;
	$comment_id = (int) $comment_id * 1;
	$comment = array(); // will hold comment to be given back
	$get_comment = $mysqli->query('SELECT comments.comment_id, comments.thecomment, comments.posted_ts, comments.updated_ts, users.username FROM comments LEFT JOIN users ON users.user_id=comments.user_id WHERE comment_id='.$comment_id);
	if ($get_comment->num_rows == 1) {
		$comment = $get_comment->fetch_assoc();
	}
	return $comment;
}

// deal with saving a new comment
function post_new_comment($comment) {
	
	// expecting $comment['post_id'], $comment['text'], $comment['user_id']
	
	global $mysqli;
	
	$rawtext_db = "'".$mysqli->escape_string($comment['text'])."'";
	
	// inspect the incoming text for
	// links (also: youtube, vimeo, jpg/gif/png?)
	// tags (#whatever)
	// user mentions (@whoever)
	// and transform them accordingly
	
	$reformatted_result = parse_text($comment['text']);
	
	// use the ['links'] and ['mentions'] and ['hashtags'] keys for anything...?
	// check to see if the @mentioned user(s) even exist?
	// notify user(s) of their mention(s)? index them?
	// index the hashtag reference(s) somewhere?
	// check the links to see if they're images/audio/video?
	
	$reformatted_text = $reformatted_result['text'];
	
	$thetext_db = "'".$mysqli->escape_string($reformatted_text)."'";
	
	$user_id_db = (int) $comment['user_id'] * 1;
	$post_id_db = (int) $comment['post_id'] * 1;
	
	$now_db = time();
	
	// insert into database
	$insert_comment_into_db = $mysqli->query("INSERT INTO comments (post_id, user_id, thecomment, rawcomment, posted_ts, updated_ts) VALUES ($post_id_db, $user_id_db, $thetext_db, $rawtext_db, $now_db, $now_db)");
	if (!$insert_comment_into_db) {
		$return_result = array('ok' => false, 'error' => 'mysql error on new post: '.$mysqli->error);
	} else {
		$new_comment_id = $mysqli->insert_id;
		$return_result = array( 'ok' => true, 'id' => $new_comment_id );
	}
	
	// send back array( 'ok' => true/false, 'error' => 'if needed' );
	return $return_result;
}

// render the comment bit
function render_comment($comment) {
	// expecting: $comment['thecomment'], $comment['username'], $comment['posted_ts'], $comment['updated_ts']
	$render = '';
	$render .= '<div class="comment">';
	$render .= '<p>'.$comment['thecomment'].'</p>';
	$render .= '<p class="comment-byline">'.$comment['username'].' '.date('m/d/Y h:i a', $comment['posted_ts']).'</p>';
	$render .= '</div>'."\n";
	return $render;
}