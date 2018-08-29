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
    if ($ssh->sftp->RemoteFileExists($remoteFile)) {
        $ssh->sftp->receiveFile($remoteFile, $localFile);
    }
} catch (smalex86\ssh\exception\BaseSshException $bse) {
    echo 'Ошибка: ' . $bse->getMessage() . PHP_EOL;
}