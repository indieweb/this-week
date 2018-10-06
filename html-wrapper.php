<!DOCTYPE html>
<html class="h-entry">
<head>
<meta charset="utf-8">
<title class="p-name">This Week in the IndieWeb: <?= IndieWeb\DateFormatter::format(date('Y-m-d', $startDate), date('Y-m-d', $endDate), false) ?></title>
<style type="text/css">
  body { 
	font: 300 100%/1.5 "Helvetica Neue", sans-serif; 
	margin: 0 auto; 
	max-width: 40em; 
	width: calc(100% - 1em);
  }
  p {
	text-align: left;
	text-indent: 0;
	margin-bottom: 1em;
  }
</style>
<link rel="webmention" href="https://webmention.io/indiewebcamp/webmention">
</head>
<body>
<img src="/this-week/images/this-week-header.png" style="width:100%;">
<main class="e-content">
<?php
	echo $html;
?>
</main>
<a href="" class="u-url"></a>
</body>
</html>
