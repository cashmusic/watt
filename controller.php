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
 * SET UP PAGE EXECUTION
 *
 ******************************************************************************/

//require_once(__DIR__.'/definitions.php');
require_once(__DIR__.'/classes/Harvard.php');
$brown = new Harvard;

$request_type = false;
$request_options = false;
$display_options = array();
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

$full_index = $brown->getIndex();


/*******************************************************************************
 *
 * GET DATA AND RENDER PAGE
 *
 ******************************************************************************/

// first set up variables
$template = '404';
$display_options['json'] = false;
if ($request_options) {
	if (count($request_options)) {
		if (strpos($request_options[0],'.json')) {
			// found a trailing.json
			$request_options[0] = str_replace('.json','',$request_options[0]);
			$display_options['json'] = true;
		}
	}
}

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
			if (isset($full_index['tags']['index'][$display_options['tag']])) {
				// set the content
				$work = array();
				foreach ($full_index['tags']['index'][$display_options['tag']] as $work_id) {
					$work[] = $full_index['work'][$work_id];
				}
				$display_options['work'] = $work;
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
		 $template = 'author';
 		if (count($request_options)) {
 			// found a tag. now what?
 			$display_options['author_id'] = $request_options[0];
 			if (isset($full_index['authors']['index'][$display_options['author_id']])) {
 				// set the content
 				$work = array();
 				foreach ($full_index['authors']['index'][$display_options['author_id']] as $work_id) {
 					$work[] = $full_index['work'][$work_id];
					$display_options['author_name'] = $full_index['work'][$work_id]['author_name'];
 				}
 				$display_options['work'] = $work;
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
	}
} else {
	/****************************************************************************
	 *
	 * MAIN PAGE (/)
	 *
	 ***************************************************************************/
	$display_options['work'] = $full_index['filtered_work'];
	$display_options['tag_list'] = $full_index['tags']['list'];
	$template = 'index';
}

// pick the correct template and echo
echo $brown->renderMustache($template, $display_options);
?>
