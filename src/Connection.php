<?php

namespace smalex86\ssh;

use Psr\Log\LoggerAwareInterface;

/**
 * Connection
 *
 * @author smirnov
 */
class Connection implements LoggerAwareInterface {
    
    use \Psr\Log\LoggerAwareTrait;
    
    /**
     * Соединение
     * @var resource
     */
    public $connection = null;
    /**
     * Настройки методов соединения -- не пригодились пока
     * @var array
     */
    private $methods = [
                'kex' => 'diffie-hellman-group1-sha1',
                'client_to_server' => [
                    'crypt' => '3des-cbc',
                    'comp' => 'none'
                ],
                'server_to_client' => [
                    'crypt' => 'aes256-cbc,aes192-cbc,aes128-cbc',
                    'comp' => 'none'
                ]
            ];
    
    
    /**
     * Получить объект и установить соединение
     * @param Psr\Log\LoggerInterface logger
     * @param string $server
     * @param int $port
     * @throws \smalex86\ssh\exception\ConnectException
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, 
            string $server, int $port = 22) {
        $this->logger = $logger;
        $callbacks = [
                'disconnect' => [$this, 'sshDisconnectCallback'],
                'debug' => [$this, 'sshDebugCallback']
            ];
        $this->connection = ssh2_connect($server, $port, [], 
                $callbacks);
        if ($this->connection === false) {
            throw new \smalex86\ssh\exception\ConnectException('Не удалось '
                    . 'соединиться с сервером ssh');
        } else {
            $this->logger->debug('Соединение с сервером ' . $server . ', port ' 
                    . $port . ' установлено');
        }
    }
    
    /**
     * Отключить соединение
     * @throws \smalex86\ssh\exception\ConnectException
     */
    public function __destruct() {
        if (is_resource($this->connection)) {
            if (ssh2_disconnect($this->connection) === false) {
                throw new \smalex86\ssh\exception\ConnectException('При '
                        . 'отключении соединения произошла ошибка');
            } else {
                $this->logger->debug('Отключение выполнено успешно');
            }
        }
    }
    
    /**
     * Авторизация паролем
     * @param string $user
     * @param string $password
     * @throws \smalex86\ssh\exception\AuthException
     */
    public function authPassword(string $user, string $password) {
        $this->logger->debug('Попытка авторизации паролем: user = ' . $user 
                . ', password = ' . $password);
        if (ssh2_auth_password($this->connection, $user, $password)) {
            $this->logger->debug('Авторизация паролем прошла успешно');
        } else {
            throw new \smalex86\ssh\exception\AuthException('Не удалось '
                    . 'авторизоваться');
        }
    }
    
    /**
     * Callback при отключении
     * @param $reason
     * @param $message
     * @param $language
     * @throws \smalex86\ssh\exception\DisconnectException
     */
    public function sshDisconnectCallback($reason, $message, $language) {
        throw new \smalex86\ssh\exception\ConnectException(sprintf('Сервер '
                . 'отключился с кодом причины [%d] и сообщением: %s', $reason, 
                $message));
    }
    
    /**
     * Callback для дебага ssh
     * @param $message
     * @param $language
     * @param $always_display
     */
    public function sshDebugCallback($message, $language, $always_display) {
        if ($this->logger) {
            $this->logger->debug('sshDebug: message = ' . $message 
                    . '; language = ' . $language . '; always_display = ' 
                    . $always_display);
        }
    }
    
}
