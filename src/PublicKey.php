<?php

namespace smalex86\ssh;

/**
 * PublicKey
 *
 * @author smirnov
 */
class PublicKey {
    
    /**
     * Имя пользователя
     * @var string
     */
    public $username = '';
    /**
     * Имя файла публичного ключа (вместе с полным путем)
     * @var string
     */
    public $pubkeyfile = '';
    /**
     * Имя файла приватного ключа (вместе с полным путем)
     * @var string 
     */
    public $privkeyfile = '';
    /**
     * Пароль к ключу (если нужен)
     * @var string
     */
    public $passphrase = '';
    /**
     * Конструктор
     * @param string $username
     * @param string $pubkeyfile
     * @param string $privkeyfile
     * @param string $passphrase
     */
    public function __construct($username, $pubkeyfile, $privkeyfile, 
            $passphrase = '') {
        $this->username = $username;
        $this->pubkeyfile = $pubkeyfile;
        $this->privkeyfile = $privkeyfile;
        $this->passphrase = $passphrase;
    }
    
}
