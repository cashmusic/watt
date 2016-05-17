<?php
/*******************************************************************************
 *
 * CASH Music publishing tool - main controller
 * http://archive.cashmusic.org/
 *
 * @package archive.org.cashmusic
 * @author CASH Music
 * @link http://cashmusic.org/
 *
 * Copyright (c) 2016, CASH Music
 * Licensed under the GNU Lesser General Public License version 3.
 * See http://www.gnu.org/licenses/lgpl-3.0.html
 *
 ******************************************************************************/



/*******************************************************************************
 *
 * INCLUDES AND VARIABLES
 *
 ******************************************************************************/

//require_once(__DIR__.'/definitions.php');
require_once(__DIR__.'/classes/Harvard.php');
$brown = new Harvard;

$request_type = false;
$request_options = false;
if (isset($_GET['p'])) {
	$request = explode('/',trim($_GET['p'],'/'));
	if (is_array($request)) {
		$request_type = array_shift($request);
		$request_options = $request;
		if ($request_type == 'writing') {
			$request_type = 'view';
		}
	}
}

$full_index = json_decode(file_get_contents(__DIR__.'/index.json'),true);

$tag_list = array();
$tag_index = array();
$published_index = array();
$now = time();
foreach ($full_index as $key => $work) {
	if (strtotime($work['date']) < $now) {
		$work['id'] = $key;
		$published_index[] = $work; // trim to current date or earlier
		if (is_array($work['tags'])) {
			if (count($work['tags'])) {
				foreach ($work['tags'] as $tag) {
					$tag_index[$tag][] = $work;
					$tag_list[] = $tag;
				}
			}
		}
	}
}
$tag_list = array_unique($tag_list); // trim tags to unique
sort($tag_list); // alphabetize

// now make it an associative array
$tmp_array = array();
foreach ($tag_list as $tag) {
	$tmp_array[]['tag'] = $tag;
}
$tag_list = $tmp_array;


/*******************************************************************************
 *
 * GET DATA AND RENDER PAGE
 *
 ******************************************************************************/

// first set up variables
$display_options = array();
$template = '404';

// figure out what template we're using
if ($request_type) {
	if ($request_type == 'view') {
		/*************************************************************************
		 *
		 * VIEW AN ARTICLE (/view)
		 *
		 ************************************************************************/
		require_once(__DIR__.'/lib/markdown/markdown.php');
		$display_options['id'] = $request_options[0]; // get article id
		if (file_exists(__DIR__.'/content/work/'.$display_options['id'].'.md')) {
			$display_options['content'] = Markdown(file_get_contents(__DIR__.'/content/work/'.$display_options['id'].'.md'));
			$work_details = json_decode(file_get_contents(__DIR__.'/content/work/'.$display_options['id'].'.json'),true);
			if ($work_details) {
				$display_options = array_merge($work_details,$display_options);
				// build tags array
				$tmp_array = array();
				foreach ($work_details['tags'] as $tag) {
					$tmp_array[]['tag'] = $tag;
				}
				$display_options['tags'] = $tmp_array;
				$display_options['display_time'] = $brown->formatTimeAgo($work_details['date']);
					$display_options['display_byline'] = $brown->formatByline($work_details['author_id']);
				$display_options['display_share'] = $brown->formatShare();
				if (isset($work_details['template'])) {
					$template = $work_details['template'];
				} else {
					$template = 'default';
				}
			}
		}
	} else if ($request_type == 'rss') {
		/*************************************************************************
		 *
		 * RSS FEED (/rss)
		 *
		 ************************************************************************/
		$template = 'rss';
	} else if ($request_type == 'podcast') {
		/*************************************************************************
		 *
		 * PODCAST FEED (/podcast)
		 *
		 ************************************************************************/
		$template = 'rss-media';
	} else if ($request_type == 'tag') {
		/*************************************************************************
		 *
		 * VIEW A SPECIFIC TAG (/tag)
		 *
		 ************************************************************************/
		$template = 'tag';
		if (count($request_options)) {
			// found a tag. now what?
			$display_options['tag'] = $request_options[0];
			$display_options['json'] = false;
			if (strpos($display_options['tag'],'.json')) {
				// found a trailing.json
				$display_options['tag'] = str_replace('.json','',$display_options['tag']);
				$display_options['json'] = true;
			}
			if (isset($tag_index[$display_options['tag']])) {
				// set the content
				$display_options['work'] = $tag_index[$display_options['tag']];
			}
			if ($display_options['json']) {
				// JSON requested, so spit it out and exit (no template)
				echo json_encode($display_options['work']);
				exit();
			}
		} else {
			// No actual tag specified. Redirect.
			header('Location: /');
			exit;
		}
	} else if ($request_type == 'redirect') {
		/*************************************************************************
		 *
		 * REDIRECT TO EXTERNAL CONTENT (/redirect)
		 *
		 ************************************************************************/
		if (isset($full_index[$request_options[0]]['url'])) {
			header('Location: ' . $full_index[$request_options[0]]['url']);
		}
	} else if ($request_type == 'author') {
		/*************************************************************************
		 *
		 * SHOW AUTHOR PAGE (/author)
		 *
		 ************************************************************************/
		// do author stuff
	}
} else {
	/****************************************************************************
	 *
	 * MAIN PAGE (/)
	 *
	 ***************************************************************************/
	$display_options['work'] = $published_index;
	$display_options['tag_list'] = $tag_list;
	$template = 'index';
}

// pick the correct template and echo
echo $brown->renderMustache($template, $display_options);
?>
