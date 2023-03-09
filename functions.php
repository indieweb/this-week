<?php

function mw_request($action, $params) {
  $ch = curl_init();
  $cwd = dirname(__FILE__);
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cwd . '/cookies.txt');
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cwd . '/cookies.txt');

  if(is_array($params)) {
    curl_setopt($ch, CURLOPT_URL, Config::$wikiAPI);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge(array(
      'action' => $action,
      'format' => 'json'
    ), $params)));
  } else {
    $params = array(
      'action' => $action,
      'format' => 'json'
    );
    curl_setopt($ch, CURLOPT_URL, Config::$wikiAPI . '?' . http_build_query($params));
  }
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $json = curl_exec($ch);
  return json_decode($json);
}

function mw_get_raw_page($rev_id) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, Config::$wikiAPI . '?oldid=' . $rev_id . '&action=raw');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  return curl_exec($ch);
}

function mw_entry($title) {
	$titleURI = pageTitleToURL($title);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, Config::$wikiBaseURL . $titleURI);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  $response = curl_exec($ch);
  $parser = new \mf2\Parser($response, Config::$wikiBaseURL . $titleURI);
  $output = $parser->parse();
  $item = false;
  if(array_key_exists('items', $output)) {
    foreach($output['items'] as $it) {
      if(array_key_exists('type', $it) && (in_array('h-entry', $it['type']) || in_array('h-event', $it['type']))) {
        $item = $it;
      }
    }
  }
  return $item;
}

function pageTitleToURL($s) {
	// Copied mostly from Mediawiki's includes/GlobalFunctions.php file
	static $needle;
	if ( is_null( $needle ) ) {
		$needle = array( '%3B', '%40', '%24', '%21', '%2A', '%28', '%29', '%2C', '%2F' );
	}

	$s = urlencode( str_replace(' ','_',$s) );
	$s = str_ireplace(
		$needle,
		array( ';', '@', '$', '!', '*', '(', ')', ',', '/', ':' ),
		$s
	);

	return $s;
}


function parse_page($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	$response = curl_exec($ch);
	$parser = new \mf2\Parser($response, $url);
	return $parser->parse();
}

function download_photo($url, $date) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	$response = curl_exec($ch);
	
	$hash = sha1($response);
	
	$archiveFolder = Config::$publicPath . 'images/' . date('Y-m-d', $date);
	if(!is_dir($archiveFolder))
	  mkdir($archiveFolder);

  $tmp = tempnam(sys_get_temp_dir(), 'iwc-'.$hash);

	file_put_contents($tmp, $response);

  $type = exif_imagetype($tmp);
  if($type == IMAGETYPE_JPEG)
    $ext = 'jpg';
  elseif($type == IMAGETYPE_PNG)
    $ext = 'png';
  elseif($type == IMAGETYPE_GIF)
    $ext = 'gif';
  else
    $ext = 'jpg';

  if(filesize($tmp) <= 5242880) { # don't download the file if it's more than 5mb (twitter's limit)
  	$filename = $hash . '.' . $ext;
  	rename($tmp, $archiveFolder . '/' . $filename);
  	chmod($archiveFolder . '/' . $filename, 0644);
  	
  	return date('Y-m-d', $date) . '/' . $filename;
  } else {
    return false;
  }
}

function join_with_and($array) {
	if(count($array) == 0)
	  return '';
	
	if(count($array) == 1)
	  return array_pop($array);
	
	$last = array_pop($array);
	
	return implode(', ', $array) . ' and ' . $last;
}

function e($text) {
  return htmlspecialchars($text);
}


function tweet_text($text) {
  
  $body = http_build_query([
    'h' => 'entry',
    'content' => $text,
  ]);
  
  $headers = [
     'Authorization: Bearer '.Config::$twitterSyndicateToken
  ];

  $ch = curl_init('https://silopub.p3k.io/micropub');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  
  return $response;
}


## Events

/**
 * For text summaries in the form "event_name was..." or "event_name is...",
 * return the event_name. If " was " or " is " are not found,
 * return the $fallback value
 */
function eventNameFromSummary(string $summary, string $fallback) {
	if (($pos = strpos($summary, ' was ')) !== false) {
		return substr($summary, 0, $pos);
	}

	if (($pos = strpos($summary, ' is ')) !== false) {
		return substr($summary, 0, $pos);
	}

	return $fallback;
}

/**
 * Extract YYYY-MM-DD from page names
 * On the wiki, event page names *should* be in
 * the format events/YYYY-MM-DD-event-slug
 */
function eventDateFromTitle(string $title) {
	preg_match('/\d{4}-\d{2}-\d{2}/', $title, $matches);
	if (count($matches) > 0) {
		return $matches[0];
	}

	return null;
}

/**
 * Add an event to the the list, grouping them
 * by event name.
 */
function addEventToList(array &$events, string $name, string $summary) {
	$key = eventNameFromSummary($summary, $name);
	if (!array_key_exists($key, $events)) {
		$events[$key] = [];
	}

	$date = eventDateFromTitle($name);
	$events[$key][$name] = $date;
}

/**
 * Convert an array of wiki event slugs and YYYY-MM-DD
 * dates into an array of links.
 */
function buildEventDateLinks(array $event_dates) {
	$results = [];
	foreach ($event_dates as $title => $date) {
		$label = $date;
		if (!$date) {
			$label = str_replace('events/', '', $title);
		}

		$results[] = sprintf('<a href="%s">%s</a>',
			Config::$wikiBaseURL . pageTitleToURL($title),
			$label
		);
	}

	return $results;
}

function buildEventNotes(array $events) {
	$output = '';
	foreach ($events as $event_name => &$options) {
		$without_dates = array_filter($options, 'is_null');
		$with_dates = array_filter($options);

		# sort events with dates, newest first
		arsort($with_dates);

		# move events without dates to the end
		$options = $with_dates + $without_dates;

		$links = buildEventDateLinks($options);

		$output .= PHP_EOL . sprintf('<p><b>%s:</b> %s</p>',
			$event_name,
			implode(', ', $links)
		);
	}

	return $output;
}

