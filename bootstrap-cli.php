<?php

require_once __DIR__ . '/bootstrap.php'; 

$logger->routeList->attach(new smalex86\logger\route\ConsoleRoute([
    'isEnabled' => true,
    'maxLevel' => $config['logger']['level'],
    'dateFormat' => $config['logger']['dateFormat']
]));
