<?php
class Harvard {
	public $lemmy = false;
	public $index = false;

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

	public function renderMustache($template_name, $vars) {
		$template = file_get_contents(__DIR__.'/../templates/'.$template_name.'.mustache');
		echo $this->lemmy->render($template, $vars);
	}

	public function getIndex() {
		return $this->index;
	}

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

	public function formatByline($author) {
		if (file_exists(__DIR__.'/../content/authors/'.$author.'.json')) {
			$details = json_decode(file_get_contents(__DIR__.'/../content/authors/'.$author.'.json'),true);
			$template = file_get_contents(__DIR__.'/../templates/byline.mustache');
			return $this->lemmy->render($template, $details);
		} else {
			return '';
		}
	}

	public function formatShare() {
		$template = file_get_contents(__DIR__.'/../templates/share.mustache');
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		return $this->lemmy->render($template, ['location' => $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']]);
	}
}
?>
