<?php

$output = parse_page('https://events.indieweb.org/');
$events1 = $output['items'][0]['children'] ?? [];

$output = parse_page('https://events.indieweb.org/archive');
$events2 = $output['items'][0]['children'] ?? [];

$eventslist = array_merge($events1, $events2);

$past = [];
$future = [];
$urls = [];


if($output) {
	foreach($eventslist as $event) {
		if(in_array('h-event', $event['type'])) {
			if(!in_array($event['properties']['url'][0], $urls)) {
				if(array_key_exists('start', $event['properties'])) {
  				# If there is an end date, use that instead of the start date
					if(array_key_exists('end', $event['properties']) && strtotime($event['properties']['end'][0]) !== false)
						$eventDate = strtotime($event['properties']['end'][0]);
					else
						$eventDate = strtotime($event['properties']['start'][0]);
					if($eventDate >= $startDate && $eventDate <= $endDate) {
						$past[] = $event;
					} elseif($eventDate >= $endDate && $eventDate <= $endDate + 86400*60) {
						$future[] = $event;
					}
				}
				$urls[] = $event['properties']['url'][0];
			}
		}
	}
}



function format_event($event) {
  global $endDate;
  
	$html = '';

	$url = array_key_exists('url', $event['properties']) ? $event['properties']['url'][0] : false;
	$name = array_key_exists('name', $event['properties']) ? $event['properties']['name'][0] : false;
	$start = array_key_exists('start', $event['properties']) ? $event['properties']['start'][0] : false;
	$end = array_key_exists('end', $event['properties']) ? $event['properties']['end'][0] : false;
	#$range = IndieWeb\DateFormatter::format($start, $end, false);

  $fullEvent = false;

	// Go fetch the event URL and look for photos
	$photos = [];
	$summary = false;
	
	if($url) {
		$details = parse_page($url);

		if($details && count($details['items']) && ($fullEvent = $details['items'][0])) {
			if(array_key_exists('photo', $fullEvent['properties'])) {
				$photos = array_merge($photos, $fullEvent['properties']['photo']);
			} else {
			  #if(strtotime($end) > time() && array_key_exists('featured', $fullEvent['properties'])) {
			  #  $photos[] = $fullEvent['properties']['featured'][0];
			  #}
			}
			$summary = array_key_exists('summary', $fullEvent['properties']) ? $fullEvent['properties']['summary'] : false;
		}
	}
	
	// If no photos were found on the detail page, check for a photo in the h-event from the list
	if(count($photos) == 0) {
  	if(isset($event['properties']['photo'][0])) {
    	$photos = $event['properties']['photo'];
  	}
	}
	
	// Override the event name from the detail page
	if($fullEvent && array_key_exists('name', $fullEvent['properties'])) {
  	$name = $fullEvent['properties']['name'][0];
	}

	if($fullEvent && array_key_exists('location', $fullEvent['properties'])) {
  	$locations = [];
		foreach($fullEvent['properties']['location'] as $loc) {
			if(is_string($loc)) {
				$locations[] = $loc;
			} elseif(array_key_exists('locality', $loc['properties'])) {
  			$locname = '';

  			if(array_key_exists('locality', $loc['properties'])) {
  			  $locname .= mb_strtoupper($loc['properties']['locality'][0]);
  			  if(array_key_exists('region', $loc['properties']))
    			  $locname .= ', ' . ($loc['properties']['region'][0]);
  			  elseif(array_key_exists('country-name', $loc['properties']))
    			  $locname .= ', ' . ($loc['properties']['country-name'][0]);
    		  $locname .= ': ';
			  }

  			$locname .= $loc['properties']['name'][0];
  			  
				$locations[] = $locname;
			} elseif(array_key_exists('name', $loc['properties'])) {
  			$locations[] = $loc['properties']['name'][0];
			}
		}
		$location = '<ul>' . implode("\n", array_map(function($e){ return '<li>'.e($e).'</li>'; }, $locations)) . '</ul>';
	} elseif(array_key_exists('location', $event['properties'])) {
		$locations = [];
		foreach($event['properties']['location'] as $loc) {
			if(is_string($loc)) {
				$locations[] = $loc;
			} elseif(array_key_exists('name', $loc['properties'])) {
				$locations[] = $loc['properties']['name'][0];
			}
		}
		$location = implode(', ', $locations) . '<br>';
	} else {
		$location = false;
	}

	if($name) {
  	$html .= "\n\n";
		$html .= '<div style="margin-bottom: 1em;" class="h-event">';
			$html .= '<div style="font-size: 1.3em; font-weight: bold;" class="p-name">' . ($url ? '<a href="'.$url.'" class="u-url">'.e($name).'</a>' : e($name)) . '</div>' . "\n";
			if($start) {
				try {
					$start = new DateTime($start);
					if($end) $end = new DateTime($end);

					if($end && $start->format('l, F j') != $end->format('l, F j')) {
  					$html .= '<time class="dt-start" datetime="'.$start->format('c').'">';
						$html .= $start->format('F j');
						$html .= '</time> - <time class="dt-end" datetime="'.$end->format('c').'">';
						$html .= $end->format('F j');
						$html .= '</time>';
					} else {
  					$html .= '<time class="dt-start" datetime="'.$start->format('c').'">';
						$html .= $start->format('l, F j');
						if($start->format('H:i:s') != '00:00:00')
						  $html .= ' at ' . $start->format('g:ia');
						$html .= '</time>';
					}
					$html .= '<br>'."\n";
				} catch(Exception $e) {
				}
			}
			if($location) {
				$html .= $location;
			}
			if($summary) {
				if(is_string($summary[0])) {
					$html .= '<div style="font-style: italic" class="p-summary">'.implode("<br>\n",array_map('auto_link', $summary)).'</div>';
				} elseif(is_array($summary[0]) && isset($summary[0]['html'])) {
					$html .= '<div style="font-style: italic" class="e-summary">'.$summary[0]['html'].'</div>';
				}
			}
			if($photos) {
				foreach($photos as $photo) {
  				$filename = download_photo($photo, $endDate);
  				if($filename) {
            $html .= '<div><img src="'.Config::$baseURL.'images/'.$filename.'" style="width:100%" class="u-photo"></div>';
          } else {
            $html .= '<!-- failed to download photo, is it over 5mb? '.htmlspecialchars($photo).' -->';
          }
				}
			}
		$html .= '</div>';
		$html .= "\n";
	}
	
	return $html;
}

if(count($past)) {
	echo '<h2 id="recent-events">Recent Events</h2>';
	echo '<p>From <a href="https://events.indieweb.org/archive">events.indieweb.org/archive</a>:</p>';
	foreach($past as $event) {
		echo format_event($event);
	}
}

if(count($future)) {
	echo '<h2 id="upcoming-events">Upcoming Events</h2>';
	echo '<p>From <a href="https://events.indieweb.org/">events.indieweb.org</a>:</p>';
	foreach($future as $event) {
		echo format_event($event);
	}
}

