<?php

use IndieWeb\DateFormatter;

require dirname(__DIR__) . '/vendor/autoload.php';

$date_param = $argv[1] ?? null;

if ($date_param) {
  $endDate = new DateTime($date_param, new DateTimeZone('US/Pacific'));
} else {
  $endDate = new DateTime();
  $endDate->setTimeZone(new DateTimeZone('US/Pacific'));

  if ($endDate->format('l') != 'Friday') {
    $endDate->modify('next friday');
  } elseif ($endDate->format('G') >= 15) {
    $endDate->modify('next friday');
  }
}

$endDate->setTime(14, 59, 0);
$startDate = clone $endDate;
$startDate->modify('-7 days');

echo $startDate->format('c') . ' to ' . $endDate->format('c') . PHP_EOL;

$startDate = $startDate->format('U');
$endDate = $endDate->format('U');
$date = DateFormatter::format(date('Y-m-d', $startDate), date('Y-m-d', $endDate), false);

ob_start();
require dirname(__DIR__) . '/generate-wiki-summary.php';
$html = ob_get_clean();

$result = file_put_contents(__DIR__ . '/wiki-summary-output.html', $html);
if (false === $result) {
  die('Could not write to wiki-summary-output.html');
}

echo 'Results saved to tests/wiki-summary-output.html' . PHP_EOL;

