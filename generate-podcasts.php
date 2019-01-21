<?php
	
$sources = ['https://huffduffer.com/tags/indieweb'];

$submissions = [];

foreach($sources as $u) {
	$feed = parse_page($u);
	$entries = $feed['items'][0]['children'];
	foreach($entries as $entry) {
  	
  	# huffduffer markup has published date in the p-author h-card, oops
  	if(isset($entry['properties']['published']))
  	  $published = $entry['properties']['published'][0];
    else
      $published = $entry['properties']['author'][0]['properties']['published'][0];
      
		if(strtotime($published) >= $startDate) {
			$name = $entry['properties']['name'][0];
			$url = $entry['properties']['url'][0];
			$published = new DateTime($published);

      #$content = $entry['properties']['content'][0]['html'];
			
			ob_start();
			echo '<div style="margin-bottom: 1em;" class="h-entry">';
		    if($name)
					echo '<div style="font-size:1.3em;font-weight:bold;"><a href="'.$url.'" class="u-url p-name">'.$name.'</a></div>';
				else
				  echo '<a href="'.$url.'" class="u-url">'.$url.'</a>';
				/*
				if($content) {
					echo '<div class="e-content">'.$content.'</div>';
				}
				*/
			echo '</div>'."\n";
			$submissions[] = ob_get_clean();	
		}
	}
}

if(count($submissions)) {
	echo '<h2 id="podcasts">Podcasts</h2>';
  	echo '<p>From <a href="https://huffduffer.com/tags/indieweb">huffduffer.com/tags/indieweb</a>:</p>'; 
	echo implode("\n", $submissions);
}
