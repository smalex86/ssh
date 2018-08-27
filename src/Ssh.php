<?php

namespace smalex86\ssh;

use smalex86\ssh\{Connection, PublicKey, Shell};
use Psr\Log\LoggerAwareInterface;

/**
 * Class for working with ssh
 *
 * @author smirnov
 */
class Ssh implements LoggerAwareInterface {
    
    use \Psr\Log\LoggerAwareTrait;
    
    /**
     * Публичный ключ
     * @var PublicKey
     */
    public $publicKey = null;
    /**
     * Оболочка
     * @var Shell
     */
    public $shell = null;
    /**
     * Соединение
     * @var Connection
     */
    public $connection = null;


    /**
     * Конструктор
     */
    public function __construct() {
        ;
    }
    
    /**
     * Задать публичный ключ -- не используется пока
     * @param PublicKey $publicKey
     */
    public function setPublicKey(PublicKey $publicKey) {
        $this->publicKey = $publicKey;
    }
    
    /**
     * Открытие соединения
     * @param string $server
     * @param int $port
     * @return Connection
     */
    public function openConnection(string $server, int $port = 22) {
        $this->connection = new Connection($this->logger, $server, $port);
        return $this->connection;
    }
    
    /**
     * Открытие оболочки
     * @param int $maxExecTime максимальное время выполнения команд в терминале
     * @param string $termType тип терминала
     * @param int $shellSleep время ожидания после подключения к терминалу, сек
     * @return Shell
     * @throws exception\BaseSshException
     */
    public function openShell(
            int $maxExecTime = 60 * 60 * 24 * 2,
            string $termType = 'xterm',
            int $shellSleep = 2) {
        if (!$this->connection) {
            throw new exception\BaseSshException('Не установлено соединение');
        }
        $this->shell = new Shell($this->logger, $this->connection, $maxExecTime, 
                $termType, $shellSleep);
        return $this->shell;
    }
    
}
