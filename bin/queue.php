#!/usr/bin/php
<?php
set_time_limit(0);
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_AUTH', '');

define('DAEMONIZE', true);
define('PID_FILE', 'framework_queue');
define('PID_NAME', 'framework_queue');
if (php_sapi_name() != "cli") {
    die("Only run in command line mode\n");
}

if (DAEMONIZE) {
    include 'Daemon.php';
    Daemon::run(PID_NAME, PID_FILE)->init($argc, $argv);
}
include_once '../Vendors/Queue.php';
$channel='demo';
$queue = Vendors\Queue::getInstance();
$counter = 0;
while(true){
   if($counter == 500){
       sleep(2);
       $counter = 0;
   }
   $counter ++;
   
   $message =  $queue->pop($channel);
   if($message){
   //do your job
   }
}
