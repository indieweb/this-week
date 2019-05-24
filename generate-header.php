<?php
$range = IndieWeb\DateFormatter::format(date('Y-m-d', $startDate), date('Y-m-d', $endDate), false);
?>
<h1><a href="https://indieweb.org/this-week/<?= date('Y-m-d', $endDate) ?>.html"><?= $range ?></a></h1>
