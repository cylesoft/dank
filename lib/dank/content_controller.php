<?php

/*
	
	dank controller for
	 - parsing text content
	 - posting new content in the database
	 - fetching lots of content
	 - editing existing content
	 - deleting existing content
	 - ... and more!
	
	functions should return an array:
		{ "ok": true/false, "error": "if there is one" }
	 
*/

require_once(__DIR__.'/../../config/config.php');
require_once(__DIR__.'/dbconn_mysql.php');
require_once(__DIR__.'/../Michelf/Markdown.inc.php');
use \Michelf\Markdown;

// parse text to link-ify links, #hashtags, and @mentions
function parse_text($text) {
	global $site_url;
	$link_regex = '/\b(https?:\/\/)?(\S+)\.(\S+)\b/i';
	$hashtag_regex = '/\#([^\s\#]+)/i';
	$mention_regex = '/\@(\S+)/i';
	$t = strip_tags($text);
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
		$the_link = trim($matches[0]);
		if (substr($the_link, 0, 4) != 'http') {
			$the_link = 'http://'.$the_link;
		}
		// check for youtube, vimeo, mp4/mov/webm, mp3, jpg/jpeg/png/gif
		if (preg_match('/(?:youtu\.be|youtube\.com)\/(?:embed\/)?(?:watch\?v=)?([^\#\&\?\s]+)/i', $the_link, $youtube_matches)) { // if youtube
			return '<div class="expanded-content"><iframe width="100%" height="100%" src="https://www.youtube.com/embed/'.$youtube_matches[1].'" frameborder="0" allowfullscreen></iframe></div>';
		} else if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/i', $the_link, $vimeo_matches)) { // if vimeo
			return '<div class="expanded-content"><iframe width="100%" height="100%" src="https://player.vimeo.com/video/'.$vimeo_matches[1].'" frameborder="0" allowfullscreen></iframe></div>';
		} else if (preg_match('/\.(?:mp4|mov|webm)$/i', $the_link)) { // if mp4/mov/webm
			return '<div class="expanded-content"><video controls="controls" src="'.$the_link.'"></video></div>';
		} else if (preg_match('/\.mp3$/i', $the_link)) { // if mp3
			return '<div class="expanded-content"><audio controls="controls" src="'.$the_link.'"></audio></div>';
		} else if (preg_match('/\.(?:jpg|jpeg|gif|png)$/i', $the_link)) { // if jpg/jpeg/png/gif
			return '<div class="expanded-content"><img src="'.$the_link.'" /></div>';
		} else { // just a link
			return '<a href="'.$the_link.'">'.$matches[0].'</a>';
		}
	}, $t);
	$t = preg_replace($hashtag_regex, '<a href="'.$site_url.'tagged/$1/">$0</a>', $t);
	$t = preg_replace($mention_regex, '<a href="'.$site_url.'by/$1/">$0</a>', $t);
	if (preg_match('/"expanded-content"/i', $t)) {
		$t .= '<div class="clear"></div>';
	}
	$t = Markdown::defaultTransform($t);
	return array('text' => $t, 'links' => $link_matches[0], 'mentions' => $mention_matches[1], 'hashtags' => $hashtag_matches[1]);
}

// show relative timestamp
// based on http://stackoverflow.com/questions/2690504/php-producing-relative-date-time-from-timestamps
function relative_timestamp($ts) {
    $diff = time() - $ts;
    if ($diff < 60){
        return sprintf($diff > 1 ? '%s seconds ago' : 'a second ago', $diff);
    }
    $diff = floor($diff/60);
    if ($diff < 60){
        return sprintf($diff > 1 ? '%s minutes ago' : 'one minute ago', $diff);
    }
    $diff = floor($diff/60);
    if ($diff < 24){
        return sprintf($diff > 1 ? '%s hours ago' : 'an hour ago', $diff);
    }
    $diff = floor($diff/24);
    if ($diff < 7){
        return sprintf($diff > 1 ? '%s days ago' : 'yesterday', $diff);
    }
    if ($diff < 30) {
        $diff = floor($diff / 7);
        return sprintf($diff > 1 ? '%s weeks ago' : 'one week ago', $diff);
    }
    $diff = floor($diff/30);
    if ($diff < 12){
        return sprintf($diff > 1 ? '%s months ago' : 'last month', $diff);
    }
    $diff = date('Y') - date('Y', $date);
    return sprintf($diff > 1 ? '%s years ago' : 'last year', $diff);
}

/*
	
	content system
	
*/

// get just the user ID owner based on post ID
function get_userid_for_postid($post_id) {
	global $mysqli;
	$post_id = (int) $post_id * 1;
	$get_user_id = $mysqli->query("SELECT user_id FROM posts WHERE post_id=$post_id");
	if ($get_user_id->num_rows == 0) {
		return 0;
	} else {
		$post_row = $get_user_id->fetch_assoc();
		if (!isset($post_row['user_id'])) {
			return 0; // anonymous user
		} else {
			return $post_row['user_id'];
		}
	}
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
		$new_file_name = 'dank_'.$unique_file_id.'.'.$file_extension;
		$new_file_path = $file_path_base.$new_file_name;
		$new_file_url = $file_url_base.$new_file_name;
		//echo '<p>'.$new_file_name.'</p>';
		//echo '<p>'.$new_file_path.'</p>';
		//echo '<p>'.$new_file_url.'</p>';
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
		
		/*
		if (isset($filter['show_nsfw']) && $filter['show_nsfw'] == false) {
			if (trim($query_where_list) != '') { $query_where_list .= ' AND '; }
			$query_where_list .= 'nsfw=0';
		}
		*/
		
		$query_where_clause .= $query_where_list;
		
	}
	
	$pagination_clause = '';
	if (isset($pagination['num']) && is_numeric($pagination['num'])) {
		$post_limit = round(abs($pagination['num'] * 1));
		$pagination_clause = 'LIMIT '.$post_limit;
		if (isset($pagination['page']) && is_numeric($pagination['page']) && $pagination['page'] * 1 > 1) {
			$pagination_clause .= ' OFFSET '.(($pagination['page'] - 1) * $post_limit);
		}
	} else {
		$pagination_clause = 'LIMIT 20';
	}
	
	$get_content_query = 'SELECT posts.*, users.username FROM posts LEFT JOIN users ON users.user_id=posts.user_id '.$query_where_clause.' ORDER BY posted_ts DESC '.$pagination_clause;
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
	
	// same as post_new_content, except expect $content['post_id']
	
	global $mysqli, $file_path_base, $file_url_base;
	
	if (!isset($content['post_id']) || !is_numeric($content['post_id']) || $content['post_id'] == 0) {
		return array('ok' => false, 'error' => 'no content ID given, dunno what to update');
	}
	
	$post_id_db = (int) $content['post_id'] * 1;
	
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
		$new_file_name = 'dank_'.$unique_file_id.'.'.$file_extension;
		$new_file_path = $file_path_base.$new_file_name;
		$new_file_url = $file_url_base.$new_file_name;
		//echo '<p>'.$new_file_name.'</p>';
		//echo '<p>'.$new_file_path.'</p>';
		//echo '<p>'.$new_file_url.'</p>';
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
			
			// get files to delete, if any
			$get_files = $mysqli->query("SELECT file_path FROM files WHERE post_id=$post_id_db");
			while ($file_row = $get_files->fetch_assoc()) {
				if (file_exists($file_row['file_path'])) {
					$delete_file_result = unlink($file_row['file_path']); // trash it.
				}
			}
			
			// delete the file rows
			$delete_file_rows = $mysqli->query("DELETE FROM files WHERE post_id=$post_id_db");
			if (!$delete_file_rows) {
				return array('ok' => false, 'error' => 'database error deleting the old post files: '.$mysqli->error);
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
	
	// update in database
	$update_post_in_db = $mysqli->query("UPDATE posts SET post_type=$post_type_db, user_id=$user_id_db, visibility=$visibility_db, thetext=$thetext_db, rawtext=$rawtext_db, nsfw=$nsfw_db, updated_ts=$now_db WHERE post_id=$post_id_db");
	if (!$update_post_in_db) {
		$return_result = array('ok' => false, 'error' => 'mysql error on new post: '.$mysqli->error);
	} else {
		$return_result = array('ok' => true, 'id' => $post_id_db); // so far so good
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
			$insert_file_into_db = $mysqli->query("INSERT INTO files (file_uniqid, post_id, file_path, file_url, image_width, image_height, duration) VALUES ($new_file_uniqid_db, $post_id_db, $new_file_path_db, $new_file_url_db, $new_file_image_width, $new_file_image_height, $new_file_duration)");
			if (!$insert_file_into_db) {
				$return_result = array('ok' => false, 'error' => 'mysql error on file row: '.$mysqli->error);
			}
		}
	}
	
	// return array
	return $return_result;
	
}

// deal with deleting a piece of content
function delete_content($content_id) {
	global $mysqli;
	
	$content_id = (int) $content_id * 1;
		
	// delete content row
	$delete_content_row = $mysqli->query("DELETE FROM posts WHERE post_id=$content_id");
	if (!$delete_content_row) {
		return array('ok' => false, 'error' => 'database error deleting the post: '.$mysqli->error);
	}
		
	// delete associated comments
	$delete_comments = $mysqli->query("DELETE FROM comments WHERE post_id=$content_id");
	if (!$delete_comments) {
		return array('ok' => false, 'error' => 'database error deleting the post comments: '.$mysqli->error);
	}
	
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
		return array('ok' => false, 'error' => 'database error deleting the post files: '.$mysqli->error);
	}
	
	// delete approval votes
	$delete_votes = $mysqli->query("DELETE FROM approval_votes WHERE post_id=$content_id");
	if (!$delete_votes) {
		return array('ok' => false, 'error' => 'database error deleting post approval votes: '.$mysqli->error);
	}
	
	// delete associated action log items
	$delete_action_log = $mysqli->query("DELETE FROM action_log WHERE post_id_affected=$content_id");
	if (!$delete_action_log) {
		return array('ok' => false, 'error' => 'database error deleting the post action log: '.$mysqli->error);
	}

	return array('ok' => true);
	
}

// get the number of currently unapproved post
function get_num_unapproved_posts() {
	global $mysqli;
	$get_num = $mysqli->query('SELECT COUNT(post_id) AS count FROM posts WHERE visibility=5');
	$num_result = $get_num->fetch_assoc();
	return $num_result['count'];
}

// get how many votes are needed to approve something
function approval_votes_needed() {
	global $mysqli;
	// get number of users, divide by 4, floor it
	$get_votes_needed = $mysqli->query("SELECT FLOOR(COUNT(user_id) / 4) AS votes_needed FROM users");
	if ($get_votes_needed != false) {
		$votes_needed_result = $get_votes_needed->fetch_assoc();
		$votes_needed = $votes_needed_result['votes_needed'] * 1;
		if ($votes_needed <= 0) {
			$votes_needed = 1; // by default, 1
		}
	}
	return $votes_needed;
}

// get how many votes are needed to disapprove something
function disapproval_votes_needed() {
	global $mysqli;
	// get number of users, divide by 2, floor it
	$get_votes_needed = $mysqli->query("SELECT FLOOR(COUNT(user_id) / 2) AS votes_needed FROM users");
	if ($get_votes_needed != false) {
		$votes_needed_result = $get_votes_needed->fetch_assoc();
		$votes_needed = $votes_needed_result['votes_needed'] * 1;
		if ($votes_needed <= 0) {
			$votes_needed = 1; // by default, 1
		}
	}
	return $votes_needed;
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
		return array('ok' => false, 'error' => 'error with post approval fetch query: ' . $mysqli->error);
	} else if ($get_post->num_rows != 1) {
		return array('ok' => false, 'error' => 'no post found with ID '.$post_id);
	} else {
		$post_info = $get_post->fetch_assoc();
		if ($post_info['visibility'] != 5) {
			return array('ok' => false, 'error' => 'post is not pending approval');
		} else if ($post_info['user_id'] == $current_user['user_id']) {
			return array('ok' => false, 'error' => 'you cannot approve your own post');
		} else {
			// delete any votes this user may have had
			$delete_votes = $mysqli->query("DELETE FROM approval_votes WHERE post_id=$post_id AND user_id=".$current_user['user_id']);
			if (!$delete_votes) {
				return array('ok' => false, 'error' => 'error with deleting previous votes: ' . $mysqli->error);
			}
			
			// add their approval vote
			$add_vote = $mysqli->query("INSERT INTO approval_votes (post_id, user_id, thevote) VALUES ($post_id, ".$current_user['user_id'].", 1)");
			if (!$add_vote) {
				return array('ok' => false, 'error' => 'error with adding new approval vote: ' . $mysqli->error);
			}
			
			// get current approval total
			$get_votes = $mysqli->query("SELECT COUNT(vote_id) AS approval_votes FROM approval_votes WHERE post_id=$post_id AND thevote=1");
			if (!$get_votes) {
				return array('ok' => false, 'error' => 'error with fetching current approval vote count: ' . $mysqli->error);
			}
			$votes_result = $get_votes->fetch_assoc();
			
			// if approval_votes >= $approval_votes_needed, set its visibility to public
			if ($votes_result['approval_votes'] >= approval_votes_needed()) {
				// approved
				$approve_the_post = $mysqli->query("UPDATE posts SET visibility=6 WHERE post_id=$post_id");
				if (!$approve_the_post) {
					return array('ok' => false, 'error' => 'error with post approval update query: ' . $mysqli->error);
				} else {
					$action_log_result = add_action_log($current_user['user_id'], 'content-approved', '', $post_info['user_id'], $post_id);
					return array('ok' => true, 'approved' => true);
				}
			} else {
				// not enough votes yet
				return array('ok' => true, 'approved' => false);
			}
		}
	}
}

// deal with disapproving of a post
function disapprove_post($post_id, $current_user) {
	global $mysqli;
	$result = array('ok' => false, 'error' => 'unknown');
	$post_id = (int) $post_id * 1;
	// get post, ensure it's visibility = 5 currently
	// ensure the user disapproving it is not the post's author
	$get_post = $mysqli->query("SELECT user_id, visibility FROM posts WHERE post_id=$post_id");
	if (!$get_post) {
		return array('ok' => false, 'error' => 'error with post approval fetch query: ' . $mysqli->error);
	} else if ($get_post->num_rows != 1) {
		return array('ok' => false, 'error' => 'no post found with ID '.$post_id);
	} else {
		$post_info = $get_post->fetch_assoc();
		if ($post_info['visibility'] != 5) {
			return array('ok' => false, 'error' => 'post is not pending approval');
		} else if ($post_info['user_id'] == $current_user['user_id']) {
			return array('ok' => false, 'error' => 'you cannot disapprove your own post');
		} else {
			// delete any votes this user may have had
			$delete_votes = $mysqli->query("DELETE FROM approval_votes WHERE post_id=$post_id AND user_id=".$current_user['user_id']);
			if (!$delete_votes) {
				return array('ok' => false, 'error' => 'error with deleting previous votes: ' . $mysqli->error);
			}
			
			// add their disapproval vote
			$add_vote = $mysqli->query("INSERT INTO approval_votes (post_id, user_id, thevote) VALUES ($post_id, ".$current_user['user_id'].", -1)");
			if (!$add_vote) {
				return array('ok' => false, 'error' => 'error with adding new disapproval vote: ' . $mysqli->error);
			}
			
			// get current approval total
			$get_votes = $mysqli->query("SELECT COUNT(vote_id) AS disapproval_votes FROM approval_votes WHERE post_id=$post_id AND thevote=-1");
			if (!$get_votes) {
				return array('ok' => false, 'error' => 'error with fetching current disapproval vote count: ' . $mysqli->error);
			}
			$votes_result = $get_votes->fetch_assoc();
			
			// if disapproval_votes >= $disapproval_votes_needed, delete it entirely
			if ($votes_result['disapproval_votes'] >= disapproval_votes_needed()) {
				// disapproved, delete it
				$delete_content_result = delete_content($post_id);
				if ($delete_content_result['ok'] == true) {
					$action_log_result = add_action_log($current_user['user_id'], 'content-rejected', '', $post_info['user_id'], $post_id);
					return array('ok' => true, 'deleted' => true);
				} else {
					return array('ok' => false, 'error' => 'error deleting the content after disapproval: '.$delete_content_result['error']);
				}
			} else {
				// not enough votes to delete it yet
				return array('ok' => true, 'deleted' => false);
			}
		}
	}
	return $result;
}

// render the post bit
function render_post($post, $current_user, $single_post_mode = false) {
	$render = ''; // we start blank
	$render .= '<div class="post-wrap" id="post-'.$post['post_id'].'">'; // start post-wrap
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
	// hide nsfw content?
	if ($current_user['show_nsfw'] == false && in_array('nsfw', $post_classes)) {
		$nsfw_hidden_state = true;
		$render .= '<p><input class="nsfw-show-anyway-btn" type="button" value="Show this NSFW content anyway..." data-post-id="'.$post['post_id'].'" /></p>';
	} else {
		$nsfw_hidden_state = false;
	}
	// post content itself, whether image or audio or video
	if ($post['post_type'] == 'image' && isset($post['files'])) {
		$render .= '<p class="post-content" '.(($nsfw_hidden_state) ? ' style="display:none"':'').'><a href="'.$post['files'][0]['file_url'].'"><img src="'.$post['files'][0]['file_url'].'" /></a></p>';
	} else if ($post['post_type'] == 'audio' && isset($post['files'])) {
		$render .= '<p class="post-content"><audio controls="controls" src="'.$post['files'][0]['file_url'].'">Looks like your browser doesn\'t support this HTML5 audio. Use Chrome.</audio></p>';
	} else if ($post['post_type'] == 'video' && isset($post['files'])) {
		$render .= '<p class="post-content" '.(($nsfw_hidden_state) ? ' style="display:none"':'').'><video controls="controls" loop="loop" src="'.$post['files'][0]['file_url'].'">Looks like your browser doesn\'t support this HTML5 video. Use Chrome.</video></p>';
	}
	// show text, if any was included
	if (isset($post['thetext']) && trim($post['thetext']) != '') {
		$render .= '<p>'.$post['thetext'].'</p>';
	}
	if (in_array('peer-approval', $post_classes) && $current_user['loggedin'] && $current_user['user_id'] != $post['user_id']) {
		$render .= '<p class="approval-form">Approve post? It will become public. <input type="button" value="&#10004; story checks out" class="button green small approve-post" data-post-id="'.$post['post_id'].'" /> <input type="button" value="&cross; lame" class="button red small disapprove-post"  data-post-id="'.$post['post_id'].'" /></p>';
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
			$render .= render_comment($comment, $current_user); // render each comment bit
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
		if ($current_user['user_id'] == $post['user_id']) {
			$render .= '<a href="/content/edit/'.$post['post_id'].'/" class="button blue">Edit &raquo;</a> ';
		}
		if ($current_user['user_id'] == $post['user_id'] || $post['user_id'] == null) {
			$render .= '<form style="display: inline;" onsubmit="return confirm(\'You sure you wanna do that?\')" action="/content/process/" method="post"><input type="hidden" name="a" value="d" /><input type="hidden" name="post_id" value="'.$post['post_id'].'" /> <input type="submit" class="button red" value="Delete &raquo;" /></form>';
		}
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
	$get_comments = $mysqli->query('SELECT comments.comment_id, comments.user_id, comments.rawcomment, comments.thecomment, comments.posted_ts, comments.updated_ts, users.username FROM comments LEFT JOIN users ON users.user_id=comments.user_id WHERE post_id='.$post_id.' ORDER BY comment_id ASC');
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
	$get_comment = $mysqli->query('SELECT comments.comment_id, comments.user_id, comments.rawcomment, comments.thecomment, comments.posted_ts, comments.updated_ts, users.username FROM comments LEFT JOIN users ON users.user_id=comments.user_id WHERE comment_id='.$comment_id);
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
		$return_result = array('ok' => false, 'error' => 'mysql error on new comment: '.$mysqli->error);
	} else {
		$new_comment_id = $mysqli->insert_id;
		$action_log_result = add_action_log($user_id_db, 'new-comment', '', get_userid_for_postid($post_id_db), $post_id_db);
		$return_result = array( 'ok' => true, 'id' => $new_comment_id );
	}
	
	// send back array( 'ok' => true/false, 'error' => 'if needed' );
	return $return_result;
}

// deal with deleting a comment
function delete_comment($comment_id) {
	global $mysqli;
	
	$comment_id = (int) $comment_id * 1;
	
	// delete comment
	$delete_comment = $mysqli->query("DELETE FROM comments WHERE comment_id=$comment_id");
	if (!$delete_comment) {
		return array('ok' => false, 'error' => 'database error deleting the comment: '.$mysqli->error);
	}
	
	return array('ok' => true);
	
}

// deal with editing a comment
function edit_comment($comment) {
	// expecting $comment['comment_id'], $comment['text']
	
	global $mysqli;
	
	$comment_id = (int) $comment['comment_id'];
	
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
	
	$now_db = time();
	
	// update database
	$update_comment = $mysqli->query("UPDATE comments SET thecomment=$thetext_db, rawcomment=$rawtext_db, updated_ts=$now_db WHERE comment_id=$comment_id");
	if (!$update_comment) {
		$return_result = array('ok' => false, 'error' => 'mysql error on edit comment update: '.$mysqli->error);
	} else {
		$return_result = array( 'ok' => true );
	}
	
	// send back array( 'ok' => true/false, 'error' => 'if needed' );
	return $return_result;
}

// render the comment bit
function render_comment($comment, $current_user) {
	$render = '';
	if ($current_user['user_id'] == $comment['user_id']) {
		$render .= '<div class="comment" id="comment-'.$comment['comment_id'].'">';
		$render .= '<div style="display:none;" id="edit-comment-'.$comment['comment_id'].'"><input type="text" id="edited-comment-'.$comment['comment_id'].'" class="your-comment" value="'.$comment['rawcomment'].'" /> <input id="save-edited-comment-'.$comment['comment_id'].'" data-comment-id="'.$comment['comment_id'].'" type="button" class="small save-comment-btn" value="save" /></div>';
		$render .= '<div class="comment-utils"><input class="tiny button edit-comment" data-comment-id="'.$comment['comment_id'].'" type="button" value="edit" /> <input class="tiny button delete-comment" data-comment-id="'.$comment['comment_id'].'" type="button" value="delete" /></div>';
	} else {
		$render .= '<div class="comment" id="comment-'.$comment['comment_id'].'">';
	}
	$render .= '<p>'.$comment['thecomment'].'</p>';
	$render .= '<p class="comment-byline">'.$comment['username'].' '.date('m/d/Y h:i a', $comment['posted_ts']).'</p>';
	$render .= '</div>'."\n";
	return $render;
}

// add an action log item
function add_action_log($user_id, $action_type, $message = '', $user_id_affected = 0, $post_id_affected = 0) {
	global $mysqli;
	
	$user_id = (int) $user_id * 1;
	
	$action_type = trim($action_type);
	$action_type_db = "'".$mysqli->escape_string($action_type)."'";
	
	if (trim($message) != '') {
		$message_db = "'".$mysqli->escape_string(trim($message))."'";
	} else {
		$message_db = 'null';
	}
	
	if ($user_id_affected * 1 > 0) {
		$user_id_affected = (int) $user_id_affected * 1;
	} else {
		$user_id_affected = 'null';
	}
	
	if ($post_id_affected * 1 > 0) {
		$post_id_affected = (int) $post_id_affected * 1;
	} else {
		$post_id_affected = 'null';
	}
	
	$add_log = $mysqli->query("INSERT INTO action_log (user_id, user_id_affected, post_id_affected, action_type, log_message, tsc) VALUES ($user_id, $user_id_affected, $post_id_affected, $action_type_db, $message_db, UNIX_TIMESTAMP())");
	if (!$add_log) {
		return array('ok' => false, 'error' => 'error inserting log: '.$mysqli->error);
	} else {
		return array('ok' => true);
	}
	
}

// get latest action logs relevant to the current user
function get_action_log($affected_user_id) {
	global $mysqli;
	$affected_user_id = (int) $affected_user_id * 1;
	// right now get 7 latest
	$get_logs = $mysqli->query("SELECT * FROM action_log WHERE user_id_affected=$affected_user_id ORDER BY tsc DESC LIMIT 7");
	$logs = array();
	if ($get_logs->num_rows > 0) {
		while ($log = $get_logs->fetch_assoc()) {
			$logs[] = $log;
		}
	}
	return $logs;
}