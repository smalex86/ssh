<?php

/**
 * Массив с настройками Logger
 */
$conf['logger'] = [
    'level'      => 7,
    'filename'   => 'ssh.log',
    'folder'     => dirname(__DIR__) . '/logs/',
    'dateFormat' => 'Y-m-d H:i:s:v T',
    'printPid'   => 'true'
];

return $conf;
