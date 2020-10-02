<?php
$range = IndieWeb\DateFormatter::format(date('Y-m-d', $startDate), date('Y-m-d', $endDate), false);
?>

<?php /*
<div style="border-radius:8px; background: #fff9b7; padding: 15px; font-weight: bold; margin-top: 20px;">
  <a href="https://2019.indieweb.org/summit" style="color:black;">Register Now: IndieWeb Summit, June 29-30, 2019</a>
</div>
*/ ?>

<h1><a href="https://indieweb.org/this-week/<?= date('Y-m-d', $endDate) ?>.html"><?= $range ?></a></h1>
