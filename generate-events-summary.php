<?php

$output = parse_page('https://indieweb.org/events');

$past = [];
$future = [];

if($output) {
	foreach($output['items'] as $event) {
		if(in_array('h-event', $event['type'])) {
			if(array_key_exists('start', $event['properties'])) {
  			# If there is an end date, use that instead of the start date
				if(array_key_exists('end', $event['properties']))
					$eventDate = strtotime($event['properties']['end'][0]);
				else
					$eventDate = strtotime($event['properties']['start'][0]);
				if($eventDate >= $startDate && $eventDate <= $endDate) {
					$past[] = $event;
				} elseif($eventDate >= $endDate && $eventDate <= $endDate + 86400*60) {
					$future[] = $event;
				}
			}
		}
	}
}

function format_event($event) {
  global $endDate;
  
	ob_start();

	$url = array_key_exists('url', $event['properties']) ? $event['properties']['url'][0] : false;
	$name = array_key_exists('name', $event['properties']) ? $event['properties']['name'][0] : false;
	$start = array_key_exists('start', $event['properties']) ? $event['properties']['start'][0] : false;
	$end = array_key_exists('end', $event['properties']) ? $event['properties']['end'][0] : false;
	#$range = IndieWeb\DateFormatter::format($start, $end, false);

  $fullEvent = false;

	// Go fetch the event URL and look for a photo
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

	if($fullEvent && array_key_exists('location', $fullEvent['properties'])) {
  	$locations = [];
		foreach($fullEvent['properties']['location'] as $loc) {
			if(is_string($loc)) {
				$locations[] = $loc;
			} elseif(array_key_exists('locality', $loc['properties'])) {
  			$locname = '';

  			if(array_key_exists('locality', $loc['properties'])) {
  			  $locname .= strtoupper($loc['properties']['locality'][0]);
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
		$location = '<ul>' . implode("\n", array_map(function($e){ return '<li>'.$e.'</li>'; }, $locations)) . '</ul>';
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
		echo '<div style="margin-bottom: 1em;" class="h-event">';
			echo '<div style="font-size: 1.3em; font-weight: bold;" class="p-name">' . ($url ? '<a href="'.$url.'" class="u-url">'.$name.'</a>' : $name) . '</div>' . "\n";
			if($start) {
				try {
					$start = new DateTime($start);
					if($end) $end = new DateTime($end);

					if($end && $start->format('l, F j') != $end->format('l, F j')) {
  					echo '<time class="dt-start" datetime="'.$start->format('c').'">';
						echo $start->format('F j');
						echo '</time> - <time class="dt-end" datetime="'.$end->format('c').'">';
						echo $end->format('F j');
						echo '</time>';
					} else {
  					echo '<time class="dt-start" datetime="'.$start->format('c').'">';
						echo $start->format('l, F j');
						if($start->format('H:i:s') != '00:00:00')
						  echo ' at ' . $start->format('g:ia');
						echo '</time>';
					}
					echo '<br>'."\n";
				} catch(Exception $e) {
				}
			}
			if($location) {
				echo $location;
			}
			if($summary) {
				echo '<div style="font-style: italic" class="p-summary">'.implode("<br>\n",array_map('auto_link', $summary)).'</div>';
			}
			if($photos) {
				foreach($photos as $photo) {
  				$filename = download_photo($photo, $endDate);
  				if($filename) {
            echo '<div><img src="'.Config::$baseURL.'images/'.$filename.'" style="width:100%" class="u-photo"></div>';
          }
				}
			}
		echo '</div>';
	}
	
	return ob_get_clean();
}

if(count($past)) {
	echo '<h2 id="recent-events">Recent Events</h2>';
	foreach($past as $event) {
		echo format_event($event);
	}
}

if(count($future)) {
	echo '<h2 id="upcoming-events">Upcoming Events</h2>';
	foreach($future as $event) {
		echo format_event($event);
	}
}
