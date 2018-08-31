<?php

namespace smalex86\ssh;

use Psr\Log\LoggerAwareInterface;
use smalex86\ssh\Connection;

/**
 * Sftp
 *
 * Сделать:
 * 1. Отправка данных в файл на удаленной машине
 * 
 * Сделано:
 * 1. Отправка файла на удаленную машину с выводом статуса (sendFile)
 * 2. Получение файла с удаленной машины с выводом статуса (receiveFile)
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
                    throw new \Exception('ошибка при чтении данных');
                } elseif ($rd) {
                    $readData += strlen($rd);
                }
                // отправка данных
                $wd = fwrite($remote, $rd);
                if ($wd === false) {
                    throw new \Exception('ошибка при отправке данных');
                } else if ($wd) {
                    $writeData += $wd;
                }
//                $this->logger->debug(sprintf('прочитано %u/%u байт и отправлено '
//                        . '%u/%u байт.', $readData, $localFileSize, $writeData, 
//                        $localFileSize));
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
    
    /**
     * Получение файла с удаленной машины
     * @param string $remoteFile
     * @param string $localFile
     * @return int количество записанных байт
     * @throws exception\SftpException
     * @throws Exception
     */
    public function receiveFile($remoteFile, $localFile) {
        $remoteFileInfo = stat('ssh2.sftp://' . intval($this->session) 
                . $remoteFile);
        if ($remoteFileInfo === false || !is_array($remoteFileInfo)) {
            throw new exception\SftpException('Не удалось получить информацию '
                    . 'об удаленном файле ' . $remoteFile);
        } 
        $remoteFileSize = $remoteFileInfo['size'];
        $rcvData = 0;
        $writeData = 0;
        $this->logger->debug(sprintf('Получение файла %s с сервер в файл %s', 
                $remoteFile, $localFile));
        try {
            $local = fopen($localFile, 'w');
            $remote = fopen('ssh2.sftp://' . intval($this->session) //. '/.'
                    . $remoteFile, 'r');
            while (!feof($remote)) {
                // получение данных
                $rd = fread($remote, self::DATA_LENGTH);
                if ($rd === false) {
                    throw new \Exception('ошибка при получении данных');
                } elseif ($rd) {
                    $rcvData += strlen($rd);
                }
                // запись данных
                $wd = fwrite($local, $rd);
                if ($wd === false) {
                    throw new \Exception('ошибка при записи данных');
                } else if ($wd) {
                    $writeData += $wd;
                }
//                $this->logger->debug(sprintf('получено %u/%u байт и записано '
//                        . '%u/%u байт.', $rcvData, $remoteFileSize, $writeData, 
//                        $remoteFileSize));
            }
            fclose($remote);
            fclose($local);
            // успех
            $this->logger->debug(sprintf('Удаленный файл %s был скопирован в '
                    . 'файл %s, было получено %u/%u байт и записано %u/%u байт.', 
                    $remoteFile, $localFile, $rcvData, $remoteFileSize, 
                    $writeData, $remoteFileSize));
            return $writeData;
        } catch (\Exception $e) {
            if (isset($local) && is_resource($local)) {
                fclose($local);
            }
            if (isset($remote) && is_resource($remote)) {
                fclose($remote);
            }
            throw new exception\SftpException('При получении данных из файла %s '
                    . 'в файл %s возникла ошибка, было получено %s байт из %s.'
                    . ' Ошибка: %s (line:%u)', $remoteFile, $localFile, 
                    $rcvData, $remoteFileSize, $e->getMessage(), $e->getLine());
        }
    }
    
    /**
     * Проверка существования удаленного файла
     * @param string $remoteFile
     * @return bool
     */
    public function RemoteFileExists($remoteFile) {
        return file_exists('ssh2.sftp://' . intval($this->session) 
                . $remoteFile);
    }
    
}
