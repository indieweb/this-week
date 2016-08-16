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
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $json = curl_exec($ch);
  return json_decode($json);
}

function mw_get_raw_page($rev_id) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, Config::$wikiAPI . '?oldid=' . $rev_id . '&action=raw');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  return curl_exec($ch);
}

function mw_entry($title) {
	$titleURI = pageTitleToURL($title);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, Config::$wikiBaseURL . $titleURI);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
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
	$response = curl_exec($ch);
	$parser = new \mf2\Parser($response, $url);
	return $parser->parse();
}

function download_photo($url, $date) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($ch);
	
	$hash = sha1($response);
	
	$archiveFolder = Config::$publicPath . 'images/' . date('Y-m-d', $date);
	if(!is_dir($archiveFolder))
	  mkdir($archiveFolder);

	$filename = $hash . '.jpg';
	
	file_put_contents($archiveFolder . '/' . $filename, $response);
	return date('Y-m-d', $date) . '/' . $filename;
}

function join_with_and($array) {
	if(count($array) == 0)
	  return '';
	
	if(count($array) == 1)
	  return array_pop($array);
	
	$last = array_pop($array);
	
	return implode(', ', $array) . ' and ' . $last;
}
