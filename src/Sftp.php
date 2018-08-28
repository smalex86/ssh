<?php

namespace smalex86\ssh;

use Psr\Log\LoggerAwareInterface;
use smalex86\ssh\Connection;

/**
 * Sftp
 *
 * Сделать:
 * 1. Получение файла с удаленной машины с выводом статуса
 * 2. Отправка данных в файл на удаленной машине
 * 
 * Сделано:
 * 1. Отправка файла на удаленную машину с выводом статуса
 * 
 * @author smirnov
 */
class Sftp implements LoggerAwareInterface {
    
    /** Размер порции данных при отправке/получении данных */
    const DATA_LENGTH = 1024 * 200;
    
    use \Psr\Log\LoggerAwareTrait;    
    /**
     * Соединение
     * @var Connection
     */
    protected $connection = null;
    /**
     * Сессия sftp
     * @var resource
     */
    protected $session = null;
    
    /**
     * Конструктор
     * @param \Psr\Log\LoggerInterface $logger
     * @param Connection $connection
     * @throws exception\SftpException
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, 
            Connection $connection) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->session = ssh2_sftp($this->connection->connection);
        if (is_resource($this->session)) {
            $this->logger->debug('Сессия sftp создана');
        } else {
            throw new exception\SftpException('При создании сессии sftp '
                    . 'произошла ошибка');
        }
    }
    /**
     * Деструктор
     * @throws \smalex86\ssh\exception\ShellException
     */
    public function __destruct() {
        if (is_resource($this->session)) {
            unset($this->session);
            $this->logger->debug('Завершение сессии sftp выполнено успешно');
        }
    }
    
    /**
     * Отправка файла на удаленную машину
     * @param string $localFile
     * @param string $remoteFile
     * @return int количество переданных байт
     * @throws exception\SftpException
     * @throws Exception
     */
    public function sendFile($localFile, $remoteFile) {
        $localFileSize = filesize($localFile);
        $readData = 0;
        $writeData = 0;
        $this->logger->debug(sprintf('Отправка файла %s на сервер в файл %s', 
                $localFile, $remoteFile));
        try {
            $local = fopen($localFile, 'r');
            $remote = fopen('ssh2.sftp://' . intval($this->session) //. '/.'
                    . $remoteFile, 'w');
            while (!feof($local)) {
                // чтение данных
                $rd = fread($local, self::DATA_LENGTH);
                if ($rd === false) {
                    throw new Exception('ошибка при чтении данных');
                } elseif ($rd) {
                    $readData += strlen($rd);
                }
                // отправка данных
                $wd = fwrite($remote, $rd);
                if ($wd === false) {
                    throw new Exception('ошибка при отправке данных');
                } else if ($wd) {
                    $writeData += $wd;
                }
                $this->logger->debug(sprintf('прочитано %u/%u байт и отправлено '
                        . '%u/%u байт.', $readData, $localFileSize, $writeData, 
                        $localFileSize));
            }
            fclose($remote);
            fclose($local);
            // успех
            $this->logger->debug(sprintf('Файл %s был отправлен в файл %s, было '
                    . 'прочитано %u/%u байт и отправлено %u/%u байт.', 
                    $localFile, $remoteFile, $readData, $localFileSize, 
                    $writeData, $localFileSize));
            return $writeData;
        } catch (\Exception $e) {
            if (isset($local) && is_resource($local)) {
                fclose($local);
            }
            if (isset($remote) && is_resource($remote)) {
                fclose($remote);
            }
            throw new exception\SftpException('При отправке данных из файла %s '
                    . 'в файл %s возникла ошибка, было отправлено %s байт из %s.'
                    . ' Ошибка: %s (line:%u)', $localFile, $remoteFile, 
                    $writeData, $localFileSize, $e->getMessage(), 
                    $e->getLine());
        }
    }
    
}
