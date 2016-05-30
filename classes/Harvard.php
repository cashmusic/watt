<?php
class Harvard {
	public $lemmy = false;
	public $index = false;
	public $request_type = false;
	public $request_options = false;

	public function __construct() {
		require(__DIR__.'/../lib/mustache/Mustache.php');
		$this->lemmy = new Mustache;

		if (!file_exists(__DIR__.'/../content/_generated_work.json')) {
			$this->index = $this->buildIndexes();
		} else {
			$this->index = array();
			$this->index['work']    = json_decode(file_get_contents(__DIR__.'/../content/_generated_work.json'),true);
			$this->index['tags']    = json_decode(file_get_contents(__DIR__.'/../content/_generated_tags.json'),true);;
			$this->index['authors'] = json_decode(file_get_contents(__DIR__.'/../content/_generated_authors.json'),true);;
		}
		$this->index['filtered_work'] = $this->filterIndexes();
	}


	/**
	 * Okay do I really fucking need to explain this function?
	 *
	 * @return array index
	 */
	public function parseRoute($route) {
		$display_options = array();
		if ($route) {
			$request = explode('/',$route);
			if (is_array($request)) {
				$request_type = array_shift($request);
				$request_options = $request;
				if ($request_type == 'writing') {
					$request_type = 'view';
				}

				$use_json = false;
				// test for JSON request
				if ($request_options) {
					if (count($request_options)) {
						if (strpos($request_options[0],'.json')) {
							// found a trailing.json
							$request_options[0] = str_replace('.json','',$request_options[0]);
							$use_json = true;
							// pass basic no-cache / CORS headers
							header('P3P: CP="ALL CUR OUR"'); // P3P privacy policy fix
							header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
							header("Cache-Control: post-check=0, pre-check=0", false);
							header("Pragma: no-cache");
							header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
							header("Access-Control-Allow-Origin: *");
							header('Access-Control-Allow-Credentials: true');
							header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Accept');
					      header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
						}
					}
				}

				return array(
					"type" => $request_type,
					"options" => $request_options,
					"json" => $use_json
				);
			}
		}
		return false;
	}

	/**
	 * Gets an instance of mustache, reads a template, and renders it to the browser
	 *
	 * @return none
	 */
	public function renderMustache($template_name, $vars) {
		if (isset($vars['header'])) {
			$vars['header'] = $this->lemmy->render($vars['header'], $vars);
		}
		if (isset($vars['footer'])) {
			$vars['footer'] = $this->lemmy->render($vars['footer'], $vars);
		}
		$template = file_get_contents(__DIR__.'/../templates/'.$template_name.'.mustache');
		echo $this->lemmy->render($template, $vars);
	}

	/**
	 * Okay do I really fucking need to explain this function?
	 *
	 * @return array index
	 */
	public function getIndex() {
		return $this->index;
	}

	/**
	 * A sorting function to put index dates into reverse chronological order
	 *
	 * @return int
	 */
	public static function sortIndex($a,$b) {
		$a_time = strtotime($a['date']);
		$b_time = strtotime($b['date']);
		if ($a_time == $b_time) {
			return 0;
		}
		return ($a_time < $b_time) ? 1 : -1;
	}

	/**
	 * This is how we don't melt the server using flat files. Reads all the data
	 * from folders once, writing out full indecies of everything found, then
	 * parsing them and building cross-reference data.
	 *
	 * @return array index
	 */
	public function buildIndexes() {
		$return = array();
		$indexes = array(
			"work"    => __DIR__.'/../content/work',
			"authors" => __DIR__.'/../content/authors',
			"tags"    => __DIR__.'/../content/tags'
		);
		foreach ($indexes as $type => $location) {
			foreach(glob($location . '/*.json') as $file) {
				$entry = json_decode(file_get_contents($file),true);
				if ($entry) {
					$key = str_replace(array('.json',$location.'/'),'',$file);
					$return[$type][$key] = $entry;
				}
			}
		}

		// move author details
		$details = $return['authors'];
		unset($return['authors']);
		$return['authors']['details'] = $details;

		// add author details to work
		foreach ($return['work'] as $key => &$entry) {
			if (is_array($return['authors']['details'][$entry['author_id']])) {
				$entry['author_name'] = $return['authors']['details'][$entry['author_id']]['name'];
				$entry['author_byline'] = $return['authors']['details'][$entry['author_id']]['byline'];
			}
		}

		// sort work to newest-first
		uasort($return['work'], array("Harvard", "sortIndex"));

		// do tag stuff
		$details = $return['tags'];
		unset($return['tags']);
		$return['tags']['details'] = $details;
		$tag_list = array();
		$tag_index = array();
		$author_index = array();
		if (is_array($return['work'])) {
			foreach ($return['work'] as $work) {
				$author_index[$work['author_id']][] = $work['id'];
				if (is_array($work['tags'])) {
					if (count($work['tags'])) {
						foreach ($work['tags'] as $tag) {
							$tag_index[$tag][] = $work['id'];
							$tag_list[] = $tag;
						}
					}
				}
			}
		}
		$tag_list = array_unique($tag_list); // trim tags to unique
		sort($tag_list); // alphabetize

		// format the tag list for mustache iteration
		$tmp_array = array();
		foreach ($tag_list as $tag) {
			$tmp_array[]['tag'] = $tag;
		}
		$tag_list = $tmp_array;

		$return['tags']['count'] = count($tag_list);
		$return['tags']['list'] = $tag_list;
		$return['tags']['index'] = $tag_index;

		$return['authors']['index'] = $author_index;

		foreach ($return as $type => $data) {
			file_put_contents(__DIR__.'/../content/_generated_'.$type.'.json',json_encode($data));
		}
		return $return;
	}

	/**
	 * Takes the index and filters it out to only show pieces with a publish date
	 * older than now() — so we can enter things with future pub dates and they
	 * stay hidden until we want them to launch.
	 *
	 * @return array
	 */
	public function filterIndexes() {
		$now = time();
		$return = array();
		foreach ($this->index['work'] as $key => $work) {
			if (strtotime($work['date']) < $now) {
				$work['id'] = $key;
				$return[] = $work; // trim to current date or earlier
			}
		}
		return $return;
	}

	/**
	 * Takes a datestamp or a string capable of being converted to a datestamp and
	 * returns a "23 minutes ago" type string for it. Now you can be cute like
	 * Twitter.
	 *
	 * @return string
	 */
	public function formatTimeAgo($time,$long=false) {
		if (is_string($time)) {
			if (is_numeric($time)) {
				$datestamp = (int) $time;
			} else {
				$datestamp = strtotime($time);
			}
		} else {
			$datestamp = $time;
		}
		$seconds = floor((time() - $datestamp));
		if ($seconds < 60) {
			$ago_str = $seconds . ' seconds ago';
		} else if ($seconds >= 60 && $seconds < 120) {
			$ago_str = '1 minute ago';
		} else if ($seconds >= 120 && $seconds < 3600) {
			$ago_str = floor($seconds / 60) .' minutes ago';
		} else if ($seconds >= 3600 && $seconds < 7200) {
			$ago_str = '1 hour ago';
		} else if ($seconds >= 7200 && $seconds < 86400) {
			$ago_str = floor($seconds / 3600) .' hours ago';
		} else if ($seconds >= 86400 && $seconds < 31536000) {
			if ($long) {
				$ago_str = date('l, F d', $datestamp);
			} else {
				$ago_str = date('d M', $datestamp);
			}
		} else {
			if ($long) {
				$ago_str = date('l, F d, Y', $datestamp);
			} else {
				$ago_str = date('d M, y', $datestamp);
			}
		}
		return $ago_str;
	}

	/**
	 * Gets details for an author and returns a formatted byline
	 *
	 * @return markup
	 */
	public function formatByline($author) {
		if (file_exists(__DIR__.'/../content/authors/'.$author.'.json')) {
			$details = json_decode(file_get_contents(__DIR__.'/../content/authors/'.$author.'.json'),true);
			$template = file_get_contents(__DIR__.'/../templates/byline.mustache');
			return $this->lemmy->render($template, $details);
		} else {
			return '';
		}
	}

	/**
	 * Formats share buttons from a template for the current URL
	 *
	 * @return markup
	 */
	public function formatShare() {
		$template = file_get_contents(__DIR__.'/../templates/share.mustache');
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		return $this->lemmy->render($template, ['location' => $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']]);
	}
}
?>
