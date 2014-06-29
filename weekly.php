<?php
chdir(dirname(__FILE__));
require_once('vendor/autoload.php');

$mwAPI = 'https://indiewebcamp.com/wiki/api.php';
$rootURI = 'http://indiewebcamp.com/';
$wikiURI = 'http://indiewebcamp.com/wiki/index.php';

function mw_request($action, $params) {
	global $mwAPI;
	
    $ch = curl_init();
    $cwd = dirname(__FILE__);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cwd . '/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cwd . '/cookies.txt');

    if(is_array($params)) {
      curl_setopt($ch, CURLOPT_URL, $mwAPI);
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
      curl_setopt($ch, CURLOPT_URL, $mwAPI . '?' . http_build_query($params));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $json = curl_exec($ch);
    return json_decode($json);
}

function mw_get_raw_page($rev_id) {
	global $mwAPI;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $mwAPI . '?oldid=' . $rev_id . '&action=raw');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    return curl_exec($ch);
}

function mw_entry($title) {
	global $rootURI;
	
	$titleURI = pageTitleToURL($title);
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rootURI . $titleURI);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    $parser = new \mf2\Parser($response, $rootURI . $titleURI);
    $output = $parser->parse();
    $item = false;
    if(array_key_exists('items', $output) && array_key_exists(0, $output['items']))
	    $item = $output['items'][0];
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


  $opts = array(
    'list' => 'recentchanges',
    'rclimit' => 500,
    'rcend' => date('YmdHis', $startDate),
    'rcprop' => 'user|comment|title|sizes|timestamp|ids',
    'rcshow' => '!minor|!redirect'
  );
	$changedPages = array();
  $changes = mw_request('query', $opts);
  if($changes && property_exists($changes, 'query') && property_exists($changes->query, 'recentchanges')) {
    foreach($changes->query->recentchanges as $change) {
      if(!array_key_exists($change->title, $changedPages)) {
			  $changedPages[$change->title] = array(
			  	'changes' => array(array(
			  		'user' => $change->user,
			  		'timestamp' => $change->timestamp,
			  		'comment' => $change->comment,
			  		'oldid' => $change->old_revid,
			  		'curid' => $change->revid
			  	)),
			  	'latestid' => $change->revid,
			  	'oldestid' => $change->old_revid,
			  	'type' => $change->type
			  );
		  } else {
			  $changedPages[$change->title]['changes'][] = array(
			  		'user' => $change->user,
			  		'timestamp' => $change->timestamp,
			  		'comment' => $change->comment,
			  		'oldid' => $change->old_revid,
			  		'curid' => $change->revid
		  	  );
			  if($change->old_revid < $changedPages[$change->title]['oldestid'])
			  	$changedPages[$change->title]['oldestid'] = $change->old_revid;
			  if($change->type == 'new')
			    $changedPages[$change->title]['type'] = 'new';
		  }
	  }
	}

	uasort($changedPages, function($a, $b){
		return count($a['changes']) < count($b['changes']);
	});
	
	$new = array();
	$changed = array();
	$toc = array(
	  'new' => array(),
	  'edit' => array()
	);

	$range = IndieWeb\DateFormatter::format(date('Y-m-d', $startDate), date('Y-m-d', $endDate), false);

	echo '<!DOCTYPE html>';
	echo '<html>';
	echo '<head>';
	  echo '<meta charset="utf-8">';
      echo '<title>IndieWebCamp ' . $range . '</title>';
      ?>
      <style type="text/css">
      p {
	      text-align: left;
	      text-indent: 0;
	      margin-bottom: 1em;
      }
      </style>
      <?php
	echo '</head>';
	echo '<body>';
	
	echo '<h1>IndieWebCamp ' . $range . '</h1>';

	echo '<p>This is an automatically-generated summary of the IndieWebCamp wiki edits from ' . $range . '</p>';

	foreach($changedPages as $title=>$page) {
	  if(!in_array($page['type'], array('new','edit')))
	    continue;

		ob_start();
		
		$chp = preg_replace('/[^a-zA-Z0-9-]/', '-', $title);
		$toc[$page['type']][$title] = array(
			'href' => $chp,
			'name' => $title,
			'edits' => count($page['changes']),
			'authors' => array(),
			'type' => $page['type']
		);
		echo '<mbp:pagebreak />';
		echo '<a name="' . $chp . '"></a>';
		echo '<h2><a href="' . $rootURI . pageTitleToURL($title) . '">' . $title . '</a></h2>';

		// Collect edits
		ob_start();
		echo '<ul>';
		foreach(array_reverse($page['changes']) as $change) {
			$query = str_replace('&','&amp;',http_build_query(array(
				'title' => $title,
				'action' => 'historysubmit',
				'diff' => $change['curid'],
				'oldid' => $change['oldid']
			)));
			echo '<li>';
				echo '<a href="' . $wikiURI . '?' . $query . '">' . date('D, F j', strtotime($change['timestamp'])) . '</a>';
				echo ' ' . strtolower($change['user']);
				echo ' <i>' . htmlspecialchars($change['comment']) . '</i>';
			echo '</li>';
			if(!in_array(strtolower($change['user']), $toc[$page['type']][$title]['authors'])) {
				$toc[$page['type']][$title]['authors'][] = strtolower($change['user']);
			}
		}
		echo '</ul>';
		$edits = ob_get_clean();
		
		$authors = strtolower(implode(', ', $toc[$page['type']][$title]['authors']));
		
		if($page['type'] == 'new') {
		    $first_edit = array_pop($page['changes']);
			echo '<p>Created by ' . $first_edit['user'] . ' on ' . date('F j', strtotime($first_edit['timestamp'])) . '</p>';

			echo $edits;

			$item = mw_entry($title);
			if($item) {
			    $page_text = $item['properties']['content'][0]['html'];
			    echo '<hr>';
				echo $page_text;
			}
		
			$new[] = ob_get_clean();
		} else {
			$query = str_replace('&','&amp;',http_build_query(array(
				'title' => $title,
				'action' => 'historysubmit',
				'diff' => $page['latestid'],
				'oldid' => $page['oldestid']
			)));
			echo '<a href="' . $wikiURI . '?' . $query . '">' . count($page['changes']) . ' edits</a> by ' . $authors;

			echo $edits;

			$changed[] = ob_get_clean();
		}
	}

	echo '<mbp:pagebreak />';
	echo '<a name="TOC"></a><a name="start"></a>';
	echo '<h2>Table of Contents</h2>';
	foreach($toc as $type=>$pages) {
	  echo '<h3>' . ($type == 'new' ? 'New Pages' : 'Changed Pages') . '</h3>';
  	echo '<ul>';
	  foreach($pages as $t) {
  		echo '<li><b><a href="#' . $t['href'] . '">' . $t['name'] . '</a></b> ' . $t['edits'] . ' edit' . ($t['edits'] == 1 ? '' : 's') . ' by ' . strtolower(implode(', ',$t['authors'])) . '</li>';
		}
    echo '</ul>';
	}

	echo '<mbp:pagebreak />';
	echo '<h2>New Pages</h2>';
	echo implode("\n", $new);

	echo '<mbp:pagebreak />';
	echo '<h2>Changed Pages</h2>';
	echo implode("\n", $changed);
	
	#echo '<pre>';
	#print_r($changedPages);

	echo '</body>';
	echo '</html>';
	



