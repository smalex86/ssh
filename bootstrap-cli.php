<?php

require_once __DIR__ . '/bootstrap.php'; 

$logger->routeList->attach(new smalex86\logger\route\ConsoleRoute([
    'isEnabled' => true,
    'maxLevel' => $configObj->logger['level'],
    'dateFormat' => $configObj->logger['dateFormat']
]));
