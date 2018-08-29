<?php

namespace smalex86\ssh;

use Psr\Log\LoggerAwareInterface;
use smalex86\ssh\Connection;

/**
 * ssh shell
 *
 * @author smirnov
 */
class Shell implements LoggerAwareInterface {
    
    use \Psr\Log\LoggerAwareTrait;
    
    /** Строка, отправляемая в оболочку перед командой */
    const PRE_CMD = 'echo "[begin]"';
    /** Регулярное выражение для поиска строки с предкомандой */
    const PRE_CMD_REGEXP = '/\[begin\]/';
    /** Строка, отправляемая в оболочку после команды */
    const POST_CMD = 'echo "[end]"';
    /** Регулярное выражение для поиска строки с посткомандой */
    const POST_CMD_REGEXP = '/\[end\]/';
    
    
    /**
     * Максимальное время выполнения команды
     * @var int
     */
    protected $maxExecTime;
    /**
     * Оболочка
     * @var resource
     */
    protected $shell;

    /**
     * Конструктор оболочки
     * @param Psr\Log\LoggerInterface logger
     * @param Connection $connection соединение
     * @param int $maxExecTime максимальное время выполнения команд в терминале
     * @param string $termType тип терминала
     * @param int $shellSleep время ожидания после подключения к терминалу, сек
     * @throws \smalex86\ssh\exception\ShellException
     */
    public function __construct(
            \Psr\Log\LoggerInterface $logger,
            Connection $connection, 
            int $maxExecTime = 60 * 60 * 24 * 2,
            string $termType = 'xterm',
            int $shellSleep = 2) {
        $this->logger = $logger;
        $this->maxExecTime = $maxExecTime;
        $this->shell = ssh2_shell($connection->connection, $termType);
        if ($this->shell === null) {
            throw new \smalex86\ssh\exception\ShellException('Ошибка при '
                    . 'запросе терминала');
        } else {
            $this->logger->debug('Терминал запущен');
            sleep($shellSleep);
        }
    }
    
    /**
     * Закрытие оболочки и деструктор
     * @throws \smalex86\ssh\exception\ShellException
     */
    public function __destruct() {
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
    public function execCommand(string $command) {
        $this->logger->debug('Команда: ' . $command);
        $this->putCmd($command);
        sleep(1);
        $result = $this->getResultStringList($command);
        sleep(1);
        $this->getStringsAfterEnd();
        return $result;
    }
    
    /**
     * Проверить на активного root пользователя
     * @return boolean
     */
    public function checkRootUser() {
        $command = 'if [ $USER = root ] ; then echo [This is root]; fi';
        $result = $this->execCommand($command);
        $this->logger->debug('Получен результат: ' . var_export($result, true));
        if (in_array('[This is root]', $result)) {
            return true;
        } else {
            return false;
        }
    }
   
    /**
     * Отправка команды с пред и пост командами
     * @param string $command
     */
    protected function putCmd(string $command) {
        fputs($this->shell, self::PRE_CMD . PHP_EOL);
        fputs($this->shell, $command . PHP_EOL);
        fputs($this->shell, self::POST_CMD . PHP_EOL);
    }
    
    /**
     * Получение строк результата работы команды
     * @param string $command
     * @return array
     */
    protected function getResultStringList(string $command) {
        $start = false;
        $startTime = time();
        $output = [];
        while ((time() - $startTime) < $this->maxExecTime) {
            $line = fgets($this->shell);
            if (isset($line) && $line != '') {
                $this->logger->debug('Получена строка: ' . trim($line));
            }
            if (stristr($line, $command)) {
                continue;
            }
            if (preg_match(self::PRE_CMD_REGEXP, $line)) {
                $start = true;
            } elseif (preg_match(self::POST_CMD_REGEXP, $line)) {
                break;
            } elseif ($start && isset($line) && $line != '') {
                $output[] = trim($line);
            }
        }
        return $output;
    }


    /**
     * Довыборка строк из потока, которые там остались после получения [end]
     * @return array
     */
    private function getStringsAfterEnd() {
        $output = [];
        while (!feof($this->shell)) {
            $line = fgets($this->shell);
            if ($line !== false) {
                $this->logger->debug('Получена строка после [end]: ' . $line);
                $output[] = $line;
            } else {
                break;
            }
        }
        return $output;
    }
    
}
