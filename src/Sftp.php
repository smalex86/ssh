<?php

namespace smalex86\ssh;

use Psr\Log\LoggerAwareInterface;
use smalex86\ssh\Connection;

/**
 * Sftp
 *
 * @author smirnov
 */
class Sftp implements LoggerAwareInterface {
    
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
    
    
    public function sendFile($localFile, $remoteFile) {
        $streamPath = 'ssh2.sftp://' . $this->session . $remoteFile;
        $this->logger->debug('Путь для открытия потока записи: ' . $streamPath);
        //$stream = fopen(, $mode);
    }
    
}
