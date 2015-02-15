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
		$return_result = array('ok' => true); // so far so good
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
			$query_where_list .= 'visibility >= '.((int) $filter['visibility'] * 1);
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
}