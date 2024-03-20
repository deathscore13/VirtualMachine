<?php

class VMRead
{
    private $stdin;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->stdin = fopen('php://stdin', 'rb');
    }

    /**
     * Деструктор
     */
    public function __destruct()
    {
        fclose($this->stdin);
    }

    /**
     * Чтение входящих данных
     * 
     * @return string|false             Строка с данными или false в случае ошибки
     */
    public function readEx(): string|false
    {
        return fgets($this->stdin);
    }

    /**
     * Чтение входящих данных
     * Данные будут расшифрованы в base64
     * 
     * @return string|false             Строка с данными или false в случае ошибки
     */
    public function read(): string|false
    {
        return base64_decode($this->readEx());
    }
}
