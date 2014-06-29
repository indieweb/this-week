<?php
chdir(dirname(__FILE__));

$startDate = strtotime('-7 days');
$endDate = time();

require('weekly.php');

