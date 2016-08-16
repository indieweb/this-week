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

$photo = false;
foreach($mf2['items'][0]['children'] as $item) {
  if(array_key_exists('photo', $item['properties'])) {
    $p = $item['properties']['photo'][0];
    if(preg_match('/images\/([0-9\-]+\/.+\.jpg)$/', $p, $match)) {
      $photo = $match[1];
      break;
    }
  }
}

if($photo) {
  $params['photo'] = '@' . Config::$publicPath . 'images/' . $photo;
} else {
  $params = http_build_query($params);
}

$ch = curl_init('https://silo.pub/micropub');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Authorization: Bearer '.Config::$twitterSyndicateToken
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

#echo $response;
