<?php
$range = IndieWeb\DateFormatter::format(date('Y-m-d', $startDate), date('Y-m-d', $endDate), false);
?>
<h1><?= $range ?></h1>
