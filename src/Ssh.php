<?php

namespace smalex86\ssh;

use smalex86\ssh\PublicKey;
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
    protected $publicKey = null;
    /**
     * Соединение
     * @var resource
     */
    protected $connection = null;
    /**
     * Интерактивный терминал
     * @var resource
     */
    protected $shell = null;
    /**
     * Время ожидания после подключения к терминалу, сек
     * @var int
     */
    protected $shellSleep = 0;
    /**
     * Максимальное время выполнения команды
     * @var int
     */
    protected $maxExecTime = 0;
    /**
     * Настройки методов соединения
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
     * Конструктор
     * @param int $shellSleep время ожидания после подключения к терминалу, сек
     * @param int $maxExecTime максимальное время выполнения команд в терминале
     */
    public function __construct(int $shellSleep = 2, 
            int $maxExecTime = 60 * 60 * 24 * 2) {
        $this->shellSleep = $shellSleep;
        $this->maxExecTime = $maxExecTime;
    }
    /**
     * Деструктор
     */
    public function __destruct() {
        $this->disconnect();
    }
    
    /**
     * Задать публичный ключ -- не используется пока
     * @param PublicKey $publicKey
     */
    public function setPublicKey(PublicKey $publicKey) {
        $this->publicKey = $publicKey;
    }
    
    /**
     * Установка соединения
     * @throws \smalex86\ssh\exception\ConnectException
     */
    public function connect(string $server, int $port = 22) {
        $this->disconnect();
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
     * Отключение соединения
     * @throws \smalex86\ssh\exception\ConnectException
     */
    public function disconnect() {
        $this->shellClose();
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
        $this->checkConnection();
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
     * Запуск терминала
     * @param string $termType
     * @throws \smalex86\ssh\exception\ShellException
     */
    public function shellStart(string $termType = 'xterm') {
        $this->checkConnection();
        if ($this->shell) {
            $this->shellClose();
        }
        $this->shell = ssh2_shell($this->connection, $termType);
        sleep($this->shellSleep);
        if ($this->shell === null) {
            throw new \smalex86\ssh\exception\ShellException('Ошибка при '
                    . 'запросе терминала');
        } else {
            $this->logger->debug('Терминал запущен');
        }
    }
    
    /**
     * Закрытие терминала
     * @throws \smalex86\ssh\exception\ShellException
     */
    public function shellClose() {
        if (is_resource($this->shell)) {
            if (fclose($this->shell) === false) {
                throw new \smalex86\ssh\exception\ShellException('Ошибка '
                        . 'закрытия терминала');
            } else {
                $this->logger->debug('Отключение терминала выполнено успешно');
            }
        }
    }
    
    /**
     * Выполнение команды в терминале
     * @param string $command
     * @return array
     */
    public function execShellCommand(string $command) {
        $this->checkConnection();
        $this->checkShell();
        $this->logger->debug('Команда: ' . $command);
        fputs($this->shell, 'echo "[start]"' . PHP_EOL);
        fputs($this->shell, $command . PHP_EOL);
        fputs($this->shell, 'echo "[end]"' . PHP_EOL);
        sleep(1);
        $output = [];
        $start = false;
        $startTime = time();
        while ((time() - $startTime) < $this->maxExecTime) {
            $line = fgets($this->shell);
            if (isset($line) && $line != '') {
                $this->logger->debug('Получена строка: ' . trim($line));
            }
            if (stristr($line, $command)) {
                continue;
            }
            if (preg_match('/\[start\]/', $line)) {
                $start = true;
            } elseif (preg_match('/\[end\]/', $line)) {
                break;
            } elseif ($start && isset($line) && $line != '') {
                $output[] = trim($line);
            }
        }
        sleep(1);
        while (!feof($this->shell)) {
            $line = fgets($this->shell);
            if ($line !== false) {
                $this->logger->debug('Получена строка после [end]: ' . $line);
            } else {
                break;
            }
        }
        return $output;
    }
    
    /**
     * Проверить на активного root пользователя
     * @return boolean
     */
    public function checkRootUser() {
        $command = 'if [ $USER = root ] ; then echo [This is root]; fi';
        $result = $this->execShellCommand($command);
        $this->logger->debug('Получен результат: ' . var_export($result, true));
        if (in_array('[This is root]', $result)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Проверка соединения
     * @throws \smalex86\ssh\exception\BaseSshException
     */
    private function checkConnection() {
        if (!$this->connection) {
            throw new \smalex86\ssh\exception\BaseSshException('Не установлено '
                    . 'соединение');
        }
    }
    /**
     * Проверка запуска терминала
     * @throws \smalex86\ssh\exception\ShellException
     */
    private function checkShell() {
        if (!$this->shell) {
            throw new \smalex86\ssh\exception\ShellException('Не запущен '
                    . 'терминал');
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
        throw new \smalex86\ssh\exception\DisconnectException(sprintf('Сервер '
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
