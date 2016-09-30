<?php
chdir(dirname(__FILE__));
require_once('vendor/autoload.php');

$today = date('Y-m-d');

$url = Config::$baseURL . $today . '.html';

$params = [
	'h' => 'entry',
	'content' => 'This week in the #indieweb '.$url
];

$mf2 = Mf2\fetch($url);

$photos = [];
$gifs = 0; $jpgs = 0;
# Iterating through the items will always be in chronological order.
# Later, we'll choose the last N photos.
foreach($mf2['items'][0]['children'] as $item) {
  if(array_key_exists('photo', $item['properties'])) {
    foreach($item['properties']['photo'] as $p) {
      if(preg_match('/images\/([0-9\-]+\/.+\.(jpg|gif))$/', $p, $match)) {
        if($match[2] == 'gif') {
          $type = 'image/gif';
          $gifs++;
        } else {
          $type = 'image/jpeg';
          $jpgs++;
        }
        $photos[] = [
          'file' => $match[1],
          'type' => $type,
        ];
      }
    }
  }
}
$photos = array_reverse($photos);

$headers = [
	 'Authorization: Bearer '.Config::$twitterSyndicateToken
];

if(count($photos)) {
  $multipart = new p3k\Multipart();
  $multipart->addArray($params);
  # if you upload a gif to twitter, you can't upload any other photos.
  # prioritize gifs in this case.
  if($gifs > 0) {
    foreach($photos as $i=>$photo) {
      if($photo['type'] == 'image/gif') {
        $multipart->addFile('photo[]', Config::$publicPath.'images/'.$photo['file'], $photo['type']);
        break;
      }
    }
  } else {
    $num = 0;
    foreach($photos as $i=>$photo) {
      if($photo['type'] == 'image/jpeg' && $num < 4) {
        $multipart->addFile('photo[]', Config::$publicPath.'images/'.$photo['file'], $photo['type']);
        $num++;
      }
    }
  }
  $body = $multipart->data();
  $headers[] = 'Content-type: ' . $multipart->contentType();
} else {
  $body = http_build_query($params);
}

$ch = curl_init('https://silo.pub/micropub');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

echo $response;
