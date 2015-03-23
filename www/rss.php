<?php

require_once(__DIR__.'/../config/config.php');
require_once(__DIR__.'/../lib/dank/content_controller.php');

$post_filter = array();

// pagination defaults
$pagination = array();
$pagination['num'] = 20;
$pagination['page'] = 1;

header('Content-type: application/rss+xml');

$latest_time = time();

$posts = fetch_content($post_filter, array(), $pagination);

if (isset($posts['error'])) { // check for an error fetching the posts
	die('<pre>'.$posts['error'].'</pre>');
} else {
	if (count($posts) > 0) {
		$latest_time = $posts[0]['posted_ts'];
	} else {
		// no posts, lol
	}
} // end posts fetch error check


$writer = new XMLWriter();
// Output directly to the user

//$writer->openURI('php://output');
$writer->openMemory();
$writer->startDocument('1.0');

$writer->setIndent(4);

// declare it as an rss document
$writer->startElement('rss');
$writer->writeAttribute('version', '2.0');
$writer->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');

// start the channel
$writer->startElement("channel");
$writer->writeElement('title', $site_name);
$writer->writeElement('description', $site_description);
$writer->writeElement('link', $site_url);
$writer->writeElement('pubDate', date('r', $latest_time));

foreach ($posts as $post) {
	if ($post['visibility'] != 6) {
		continue;
	}
	$poster_username = ((isset($post['username']) && trim($post['username']) != '') ? $post['username'] : 'Anonymous');
	//echo '<pre>'.print_r($post, true).'</pre>';
	$writer->startElement("item");
	$writer->writeElement('title', 'dank post '.$post['post_id'].' by '.$poster_username);
	$writer->writeElement('link', $site_url.'content/'.$post['post_id'].'/');
	$writer->writeElement('guid', $site_url.'content/'.$post['post_id'].'/');
	$writer->writeElement('pubDate', date('r', $post['posted_ts']));
	$writer->startElement('description');
	$description = '';
	if ($post['post_type'] == 'image' && isset($post['files'])) {
		$description .= '<p class="post-content"><a href="'.$site_url.$post['files'][0]['file_url'].'"><img src="'.$site_url.$post['files'][0]['file_url'].'" /></a></p>';
	} else if ($post['post_type'] == 'audio' && isset($post['files'])) {
		$description .= '<p class="post-content"><audio controls="controls" src="'.$site_url.$post['files'][0]['file_url'].'">Looks like your browser doesn\'t support this HTML5 audio. Use Chrome.</audio></p>';
	} else if ($post['post_type'] == 'video' && isset($post['files'])) {
		$description .= '<p class="post-content"><video controls="controls" loop="loop" src="'.$site_url.$post['files'][0]['file_url'].'">Looks like your browser doesn\'t support this HTML5 video. Use Chrome.</video></p>';
	}
	$description .= $post['thetext'];
	$writer->writeCData($description);
	$writer->endElement(); // end description
	//$writer->writeElement('description', $post['rawtext']);
	$writer->endElement(); // end item
}

$writer->endElement(); // end channel
$writer->endElement(); // end rss
$writer->endDocument(); // end the whole thing

echo $writer->outputMemory(TRUE);
