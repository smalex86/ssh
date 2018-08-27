<?php

require_once dirname(__DIR__) . '/vendor/autoload.php'; 

$config = include_once dirname(__DIR__) . "/config/config.php";

use smalex86\logger\Logger;

$logger = new Logger();
$logger->routeList->attach(new smalex86\logger\route\FileRoute([
    'isEnabled' => true,
    'maxLevel' => $config['logger']['level'],
    'logFile' => 'ssh.log',
    'folder' => $config['logger']['folder'],
    'dateFormat' => $config['logger']['dateFormat']
]));
