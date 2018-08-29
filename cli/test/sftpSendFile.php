<?php

require_once dirname(__DIR__) . '/bootstrap-cli.php'; 

use smalex86\ssh\Ssh;

try {
    $ssh = new Ssh();
    $ssh->setLogger($logger);
    $ssh->openConnection($configObj->access['server'])->authPassword(
            $configObj->access['user'], $configObj->access['password']);  
    $localFile = '/tmp/test.local';
    $remoteFile = '/tmp/test.remote';
    $ssh->openSftp();
    if (file_exists($localFile)) {
        echo 'локальный файл найден' . PHP_EOL;
        $ssh->sftp->sendFile($localFile, $remoteFile);
    }
} catch (smalex86\ssh\exception\BaseSshException $bse) {
    echo 'Ошибка: ' . $bse->getMessage() . PHP_EOL;
}