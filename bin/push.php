<?php
include_once '../Vendors/Queue.php';
$channel='demo';
$queue = Vendors\Queue::getInstance();
$message = '{"aaa":"hello"}'.date("H:i:s");
$rst =  $queue->push($message,$channel);
