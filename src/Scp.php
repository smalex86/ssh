<?php

namespace smalex86\ssh;

use Psr\Log\LoggerAwareInterface;
use smalex86\ssh\Connection;

/**
 * Scp
 *
 * @author smirnov
 */
class Scp implements LoggerAwareInterface {
    
    use \Psr\Log\LoggerAwareTrait;
    
    /**
     * Соединение
     * @var Connection
     */
    protected $connection = null;
    
    /**
     * Конструктор
     * @param \Psr\Log\LoggerInterface $logger
     * @param Connection $connection
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, 
            Connection $connection) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->logger->debug('Объект scp создан');
    }
    
    /**
     * Копирование файла на удаленный сервер
     * @param string $localFile
     * @param string $remoteFile
     * @param int $createMode
     * @throws exception\ScpException
     */
    public function sendFile(string $localFile, string $remoteFile, 
            int $createMode = 0644) {
        $this->logger->debug('Отправка локального файла "' . $localFile 
                . '" в удаленный файл "' . $remoteFile . '"');
        $result = ssh2_scp_send($this->connection->connection, "$localFile", 
                "$remoteFile", $createMode);
        if (!$result) {
            throw new exception\ScpException('Произошла ошибка при отправке '
                    . 'файла на удаленный сервер');
        } else {
            $this->logger->debug('Файл "' . $localFile . '" был успешно '
                    . 'отправлен в "' . $remoteFile . '"');
        }
    }
    
    /**
     * Копирование файла с удаленного сервера
     * @param string $remoteFile
     * @param string $localFile
     * @throws exception\ScpException
     */
    public function receiveFile(string $remoteFile, string $localFile) {
        $this->logger->debug('Получение удаленного файла "' . $remoteFile 
                . '" в локальный файл "' . $localFile . '"');
        $result = ssh2_scp_recv($this->connection->connection,  
                "$remoteFile", $localFile);
        if (!$result) {
            throw new exception\ScpException('Произошла ошибка при получении '
                    . 'файла с удаленного сервера');
        } else {
            $this->logger->debug('Файл "' . $remoteFile . '" был успешно '
                    . 'получен в "' . $localFile . '"');
        }
    }
    
}
