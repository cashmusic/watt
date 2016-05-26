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
 *
 *
 * Main settings are stored in the settings.json file. Format:
 *
 *    {
 *			"featured_work":[
 *				"work_id",
 * 			"anotherworkid"
 *			],
 *			"secondary_work":[
 * 			"yetanotherworkid"
 *			],
 *			"tertiary_work":[
 * 			"knockknockwhosthere-workid"
 *			],
 *			"featured_authors":[
 *				"kurtvonnegut"
 *			],
 *			"featured_tags":[
 *				"open",
 * 			"idea"
 *			],
 *			"template":"index"
 *		}
 *
 ******************************************************************************/



/*******************************************************************************
 *
 * SET UP PAGE EXECUTION
 *
 ******************************************************************************/

// get our education class
require_once(__DIR__.'/classes/Harvard.php');
$brown = new Harvard;

// get main settings
$main_settings = json_decode(file_get_contents(__DIR__.'/settings.json'),true);

// parse the route
if (isset($_GET['p'])) {
	$parsed_route = $brown->parseRoute($_GET['p']);
} else {
	$parsed_route = false;
}

// set json true/false based on parsed route
$display_options['json'] = false;
if ($parsed_route['json']) {
	$display_options['json'] = true;
}

// grab the full index from the Harvard class
$full_index = $brown->getIndex();

if (file_exists(__DIR__.'/templates/header.mustache')) {
	$display_options['header'] = file_get_contents(__DIR__.'/templates/header.mustache');
}

if (file_exists(__DIR__.'/templates/footer.mustache')) {
	$display_options['footer'] = file_get_contents(__DIR__.'/templates/footer.mustache');
}


/*******************************************************************************
 *
 * GET DATA AND RENDER PAGE
 *
 ******************************************************************************/

// first set up variables
$template = '404';

// figure out what template we're using
if ($parsed_route) {
	if ($parsed_route['type'] == 'view') {
		/*************************************************************************
		 *
		 * VIEW AN ARTICLE (/view)
		 *
		 ************************************************************************/
		require_once(__DIR__.'/lib/markdown/markdown.php');
		$display_options['id'] = $parsed_route['options'][0]; // get article id
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
	} else if ($parsed_route['type'] == 'rss') {
		/*************************************************************************
		 *
		 * RSS FEED (/rss)
		 *
		 ************************************************************************/
		$template = 'rss';
	} else if ($parsed_route['type'] == 'podcast') {
		/*************************************************************************
		 *
		 * PODCAST FEED (/podcast)
		 *
		 ************************************************************************/
		$template = 'rss-media';
	} else if ($parsed_route['type'] == 'tag') {
		/*************************************************************************
		 *
		 * VIEW A SPECIFIC TAG (/tag)
		 *
		 ************************************************************************/
		$template = 'tag';
		if (count($parsed_route['options'])) {
			// found a tag. now what?
			$display_options['tag'] = $parsed_route['options'][0];
			if (isset($full_index['tags']['index'][$display_options['tag']])) {
				// set the content
				$work = array();
				foreach ($full_index['tags']['index'][$display_options['tag']] as $work_id) {
					$work[] = $full_index['work'][$work_id];
				}
				$display_options['work'] = $work;
				$display_options['tag_list'] = $full_index['tags']['list'];
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
	} else if ($parsed_route['type'] == 'redirect') {
		/*************************************************************************
		 *
		 * REDIRECT TO EXTERNAL CONTENT (/redirect)
		 *
		 ************************************************************************/
		if (isset($full_index[$parsed_route['options'][0]]['url'])) {
			header('Location: ' . $full_index[$parsed_route['options'][0]]['url']);
		}

	} else if ($parsed_route['type'] == 'video') {
		/*************************************************************************
		 *
		 * REDIRECT TO EXTERNAL CONTENT (/video)
		 *
		 ************************************************************************/
		if (isset($full_index[$parsed_route['options'][0]]['url'])) {
			header('Location: ' . $full_index[$parsed_route['options'][0]]['url']);
		}

	} else if ($parsed_route['type'] == 'author') {
		/*************************************************************************
		 *
		 * SHOW AUTHOR PAGE (/author)
		 *
		 ************************************************************************/
		 $template = 'author';
 		if (count($parsed_route['options'])) {
 			// found a tag. now what?
 			$display_options['author_id'] = $parsed_route['options'][0];
 			if (isset($full_index['authors']['index'][$display_options['author_id']])) {
 				// set the content
 				$work = array();
 				foreach ($full_index['authors']['index'][$display_options['author_id']] as $work_id) {
 					$work[] = $full_index['work'][$work_id];
					$display_options['author_name'] = $full_index['work'][$work_id]['author_name'];
 				}
 				$display_options['work'] = $work;
				$display_options['tag_list'] = $full_index['tags']['list'];
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

	$display_options['featured_work'] = array();
	$display_options['secondary_work'] = array();
	$display_options['tertiary_work'] = array();
	$display_options['featured_tags'] = $main_settings['featured_tags'];
	$display_options['featured_authors'] = array();

	foreach ($main_settings['featured_work'] as $work_id) {
		require_once(__DIR__.'/lib/markdown/markdown.php');
		 if (file_exists(__DIR__.'/content/work/'.$work_id.'.md')) {
			$full_index['work'][$work_id]['content'] = Markdown(file_get_contents(__DIR__.'/content/work/'.$work_id.'.md'));
		}
		$display_options['display_share'] = $brown->formatShare();
		$display_options['featured_work'][] = $full_index['work'][$work_id];
	}
	foreach ($main_settings['secondary_work'] as $work_id) {
		$display_options['secondary_work'][] = $full_index['work'][$work_id];
	}
	foreach ($main_settings['tertiary_work'] as $work_id) {
		$display_options['tertiary_work'][] = $full_index['work'][$work_id];
	}
	foreach ($main_settings['quaternary_work'] as $work_id) {
		$display_options['quaternary_work'][] = $full_index['work'][$work_id];
	}
	foreach ($main_settings['featured_authors'] as $author_id) {
		$display_options['featured_authors'][] = $full_index['authors']['index'][$author_id];
	}


	$display_options['work'] = $full_index['filtered_work'];
	$display_options['tag_list'] = $full_index['tags']['list'];
	$template = $main_settings['template'];

}

// pick the correct template and echo
echo $brown->renderMustache($template, $display_options);
?>
