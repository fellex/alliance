<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
//ini_set('max_execution_time', 1200);

require 'Base.php';
require 'MySQL.php';

$config = include 'config.php';
$base = new Base($config);
$base->run();
exit;
