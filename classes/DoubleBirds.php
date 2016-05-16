<?php
class DoubleBirds {


	/**
	 * Takes a datestamp or a string capable of being converted to a datestamp and
	 * returns a "23 minutes ago" type string for it. Now you can be cute like
	 * Twitter.
	 *
	 * @return string
	 */
	public static function formatTimeAgo($time,$long=false) {
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

	public static function formatByline($author) {
		require_once(__DIR__.'/../lib/mustache/Mustache.php');
		$lemmy = new Mustache;
		if (file_exists(__DIR__.'/../authors/'.$author.'.json')) {
			$details = json_decode(file_get_contents(__DIR__.'/../authors/'.$author.'.json'),true);
			$template = file_get_contents(__DIR__.'/../templates/byline.mustache');
			return $lemmy->render($template, $details);
		} else {
			return '';
		}
	}

	public static function formatShare() {
		require_once(__DIR__.'/../lib/mustache/Mustache.php');
		$lemmy = new Mustache;
		$template = file_get_contents(__DIR__.'/../templates/share.mustache');
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		return $lemmy->render($template, ['location' => $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']]);
	}
}
?>
