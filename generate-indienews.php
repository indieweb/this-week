<?php
	
$indienews = ['https://news.indieweb.org/en','https://news.indieweb.org/sv','https://news.indieweb.org/de','https://news.indieweb.org/fr'];

$submissions = [];


foreach($indienews as $u) {
	$feed = parse_page($u);
	$entries = $feed['items'][0]['children'];
	foreach($entries as $entry) {
		if(isset($entry['properties']['published'][0]) && strtotime($entry['properties']['published'][0]) >= $startDate) {
			
			$name = $entry['properties']['name'][0];
			$url = $entry['properties']['url'][0];
			$published = new DateTime($entry['properties']['published'][0]);
			$author = $entry['properties']['author'][0]['properties']['url'][0];

			if(strlen($name) > 140) {
				$content = $name;
				$name = ''; #preg_replace('|https?://|','',$author);
			} else {
				$content = false;
			}
			$author_name = preg_replace('/(https?:\/\/|\/$)/','',$author);
			
			ob_start();
			echo '<div style="margin-bottom: 1em;" class="h-entry">';
		    if($name)
					echo '<div style="font-size:1.3em;font-weight:bold;"><a href="'.$url.'" class="u-url p-name">'.$name.'</a></div>';
				else
				  echo '';
				echo '<div>';
				  echo 'by <a href="'.$author.'" class="p-author h-card">'.$author_name.'</a>';
				  echo ' on <a href="'.$url.'"><time class="dt-published" datetime="'.$published->format('c').'">'.$published->format('F j').'</time></a>';
			  echo '</div>';
				if($content) {
					echo '<div>'.auto_link($content).'</div>';
				}
			echo '</div>'."\n";
			$submissions[] = ob_get_clean();
			
		}
	}
}


if(count($submissions)) {
	echo '<h2 id="news">Posts about the IndieWeb</h2>';
  	echo '<p>From <a href="https://news.indieweb.org/en">news.indieweb.org</a>:</p>'; 	
	echo implode("\n", $submissions);
}
