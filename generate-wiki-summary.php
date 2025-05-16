<?php

  $opts = array(
    'list' => 'recentchanges',
    'rclimit' => 500,
    'rcend' => date('YmdHis', $startDate),
    'rcprop' => 'user|comment|title|sizes|timestamp|ids',
    'rcshow' => '!redirect'
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

	uasort($changedPages, function($a, $b) {
		if (count($a['changes']) == count($b['changes'])) {
			return 0;
		}

		return (count($a['changes']) > count($b['changes'])) ? -1 : 1;
	});
	
	$new = array();
	$changed = array();
	$newpeople = array();
	$eventnotes = array();
	$toc = array(
	  'new' => array(),
	  'edit' => array()
	);

	foreach($changedPages as $title=>$page) {
	  if(!in_array($page['type'], array('new','edit')))
	    continue;

		ob_start();

		$chp = preg_replace('/[^a-zA-Z0-9-]/', '-', $title);

		$authors = [];
		foreach(array_reverse($page['changes']) as $change) {
			if(preg_match('/(prompted by .+) (https:\/\/indieweb(?:camp.com|.org)[^ ]+)? ?and dfn added by (.+)/', $change['comment'], $match)) {
				$user = $match[3];
			} else {
				$user = $change['user'];
			}
			if(!in_array(strtolower($user), $authors)) {
				$authors[] = strtolower($user);
			}
		}

		// Summary
		if($page['type'] == 'new') {

			// Subpages of user-pages are excluded (e.g. custom CSS or personal test pages)
			if(preg_match('/^user:/i', $title) && strpos($title, '/') === false)
				$page['isuser'] = true;
			else
				$page['isuser'] = false;

			$page['is_event_notes'] = false;
			if (strpos($title, 'events/') === 0) {
				// Pages starting with `events/` go in a separate section
				// events/2022-10-26-hwc-pacific
				$page['is_event_notes'] = true;

				$item = mw_entry($title);
				$summary = '';
				if ($item && array_key_exists('summary', $item['properties'])) {
					$summary = $item['properties']['summary'][0];
				}

				addEventToList($eventnotes, $title, $summary);
			} else

			// Hide user: and template: pages (only include user pages and other pages that don't begin with user: or template:)
			if($page['isuser']
			 || (!$page['isuser'] && !preg_match('/^(user|template|category|mediawiki):/i', $title))) {
			
				echo '<h3><a href="' . Config::$wikiBaseURL . pageTitleToURL($title) . '">' . $title . '</a></h3>';
	
				$item = mw_entry($title);
				if($item && array_key_exists('summary', $item['properties'])) {
				    echo '<p>' . $item['properties']['summary'][0] . '</p>';
				}
	
			    $first_edit = $page['changes'][count($page['changes'])-1];
				if(preg_match('/(prompted by .+) (https:\/\/indieweb(?:camp.com|.org)[^ ]+)? ?and dfn added by (.+)/', $first_edit['comment'], $match)) {
					$by = $match[3];
				} else {
					$by = $first_edit['user'];
				}
	
				$authors = array_diff($authors, [strtolower($by)]);
				$authors_str = strtolower(join_with_and($authors));
	
				echo '<p style="font-size:0.8em;">';
					echo 'Created by ' . $by . ' on ' . date('l', strtotime($first_edit['timestamp']));
					$num_changes = count($page['changes'])-1;
					if(count($authors) > 1) {
						echo ' with ' . $num_changes . ' more edit'.($num_changes == 1 ? '' : 's').' by ' . $authors_str;
					} else {
						if(count($page['changes']) > 1) {
							echo ' and edited ' . $num_changes . ' more time'.($num_changes == 1 ? '' : 's');
						}
					}
				echo '</p>';
			}
			
		} else {
			if(count($changed) < 10) {
				if(!preg_match('/^(user|template|mediawiki|category):/i', $title) && count($page['changes']) > 1) {
					$authors_str = strtolower(join_with_and($authors));
			
					$query = str_replace('&','&amp;',http_build_query(array(
						'title' => $title,
						'action' => 'historysubmit',
						'diff' => $page['latestid'],
						'oldid' => $page['oldestid']
					)));

					echo '<li><b><a href="' . Config::$wikiBaseURL . pageTitleToURL($title) . '">' . $title . '</a></b> <a href="' . Config::$wikiURL . '?' . $query . '">' . count($page['changes']) . ' edit'.(count($page['changes']) == 1 ? '' : 's').'</a> by ' . $authors_str . '</li>';
				}
			}
		}
	
		$content = ob_get_clean();
		if(trim($content)) {
			if($page['type'] == 'new') {
				if($page['isuser'])
					$newpeople[] = $content;
				else
					$new[] = $content;
			} else {
				$changed[] = $content;
			}
		}
	}
	
?>

<?php if(count($newpeople)): ?>
<h2 id="new-community-members">New Community Members</h2>
<p>From <a href="https://indieweb.org/wiki/index.php?title=Special%3ANewPages&namespace=2">IndieWeb Wiki: New User Pages</a>:</p>
<?= implode("\n", $newpeople) ?>
<?php endif; ?>

<?php if(count($new)): ?>
<h2 id="new-wiki-pages">Top New Wiki Pages</h2>
<p>From <a href="https://indieweb.org/wiki/index.php?title=Special%3ANewPages&namespace=0">IndieWeb Wiki: New Pages</a>:</p>
<?= implode("\n", $new) ?>
<?php endif; ?>

<?php if(count($eventnotes)): ?>
<h2 id="new-event-notes">New Event Notes</h2>
<p>From <a href="https://indieweb.org/wiki/index.php?title=Special%3ANewPages&namespace=0">IndieWeb Wiki: New Pages</a>:</p>
<?= buildEventNotes($eventnotes) ?>
<?php endif; ?>


<?php if(count($changed)): ?>
<h2 id="changed-wiki-pages">Top Edited Wiki Pages</h2>
<p>From <a href="https://indieweb.org/wiki/index.php?namespace=0&title=Special%3ARecentChanges">IndieWeb Wiki: Recent Changes</a>:</p>
<ul>
<?= implode("\n", $changed) ?>
</ul>
<?php endif; ?>

