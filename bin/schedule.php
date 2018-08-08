#!/usr/bin/php
<?php
set_time_limit(0);
include_once '../Vendors/Schedule.php';
$schedule = Vendors\Schedule::getInstance([
    'driver' => 'file',
    'file' => [
        'schedule' => '/tmp/sch.log'
    ]
]);
$rst = $schedule->run();
var_dump($rst);
