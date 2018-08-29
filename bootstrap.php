<?php

require_once __DIR__ . '/vendor/autoload.php'; 

/*
 * Начало - Загрузка настроек 
 */
// определение пути для файлов конфигурации
$configPath = __DIR__ . '/config/'; 
// загрузка файлов конфигурации
$config = [];
$files = glob($configPath . '*.php');
foreach ($files as $file) {
  $config = array_merge($config, include $file);
}
// конфигурация
$configObj = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS);
/*
 * Конец - Загрузка настроек
 */

use smalex86\logger\Logger;

$logger = new Logger();
$logger->routeList->attach(new smalex86\logger\route\FileRoute([
    'isEnabled' => true,
    'maxLevel' => $configObj->logger['level'],
    'logFile' => 'ssh.log',
    'folder' => $configObj->logger['folder'],
    'dateFormat' => $configObj->logger['dateFormat']
]));
