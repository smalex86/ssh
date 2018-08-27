<?php

require_once dirname(__DIR__) . '/bootstrap-cli.php'; 

use smalex86\ssh\Ssh;

try {
    $ssh = new Ssh();
    $ssh->setLogger($logger);
    $ssh->connect($configObj->access['server']);
    $ssh->authPassword($configObj->access['user'], $configObj->access['password']);
    $ssh->shellStart();
    echo 'результат = ' . var_export($ssh->execShellCommand('sudo -i ' . PHP_EOL . $configObj->access['password']), true) . PHP_EOL;
    echo 'результат = ' . var_export($ssh->execShellCommand('service --status-all'), true) . PHP_EOL;
    if ($ssh->checkRootUser()) {
        echo 'Current user is root' . PHP_EOL;
    } else {
        echo 'Current user is not root' . PHP_EOL;
    }
    $ssh->shellClose();
    $ssh->disconnect();
} catch (smalex86\ssh\exception\BaseSshException $bse) {
    echo 'Ошибка: ' . $bse->getMessage() . PHP_EOL;
}