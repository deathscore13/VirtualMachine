<?php

class VMWrite
{
    private $stdout;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->stdout = fopen('php://stdout', 'wb');
    }

    /**
     * Деструктор
     */
    public function __destruct()
    {
        fclose($this->stdout);
    }

    /**
     * Вывод данных
     * 
     * @param string $buffer
     */
    public function writeEx(string $buffer): void
    {
        fwrite($this->stdout, $buffer."\n");
    }

    /**
     * Вывод данных
     * Данные будут зашифрованы в base64
     * 
     * @param string $buffer
     */
    public function write(string $buffer): void
    {
        $this->writeEx(base64_encode($buffer));
    }
}
