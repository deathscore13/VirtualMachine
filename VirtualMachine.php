<?php

/**
 * VirtualMachine
 * 
 * Виртуальная машина для запуска и выполнения действий с программами для PHP 8.0.0+
 * https://github.com/deathscore13/VirtualMachine
 */

class VirtualMachine
{
    private array $desc = [
        ['pipe', 'rb'],     // 0 | stdin
        ['pipe', 'wb'],     // 1 | stdout
        ['file', '', 'a']   // 2 | stderr
    ];
    private array $pipes = [];
    private mixed $proc = false;
    private bool $closed = false;

    /**
     * Конструктор
     * 
     * @param string $errPath           Директория для записи ошибок относительно папки logs
     * @param ?string $cmd              Команда для запуска. Например: PHP_BINARY.' -dauto_prepend_file='.__DIR__.'/prepend.php test.php'
     * @param ?string $cwd              Абсолютный путь рабочей директории. null - директория текущего процесса
     * @param ?array $env               Переменные среды в виде ['алиас' => 'значение']
     * @param ?array $options           Допустимые значения смотрите на https://www.php.net/manual/ru/function.proc-open.php
     */
    public function __construct(string $errPath, string $cmd, ?string $cwd = null, ?array $env = null, ?array $options = null)
    {
        $errPath = __DIR__.'/logs/'.$errPath;
        if (!is_dir($errPath))
            mkdir($errPath, 0777, true);

        $this->desc[2][1] = $errPath.'/'.date('Y-m-d').'.log';

        if (($this->proc = proc_open($cmd, $this->desc, $this->pipes, $cwd, [], $options)) === false)
            throw new Exception('proc_open() failed');

        if ($env)
        {
            $dir = __DIR__.'/env';
            if (!is_dir($dir))
                mkdir($dir, 0777, true);

            $fenv = fopen($dir.'/'.$this->status()['pid'], 'wb');
            fwrite($fenv, json_encode($env));
            fclose($fenv);
        }
    }

    /**
     * Деструктор. Ждёт завершение процесса и очищает stdin/stdout
     */
    public function __destruct()
    {
        if (!$this->closed)
        {
            $this->_rmenv();
            
            fclose($this->pipes[0]);
            fclose($this->pipes[1]);
            proc_close($this->proc);
        }
    }

    /**
     * Получение информации о процессе
     * 
     * @return array                    https://www.php.net/manual/ru/function.proc-get-status.php
     */
    public function status(): array
    {
        return proc_get_status($this->proc);
    }

    /**
     * Передача данных в процесс
     * 
     * @param string $buffer            Строка с данными
     */
    public function writeEx(string $buffer): void
    {
        fwrite($this->pipes[0], $buffer."\n");
    }

    /**
     * Передача данных в процесс
     * Данные будут зашифрованы в base64
     * 
     * @param string $buffer            Строка с данными
     */
    public function write(string $buffer): void
    {
        $this->write(base64_encode($buffer));
    }

    /**
     * Чтение данных из процесса
     * 
     * @return string|false             Строка с данными или false в случае ошибки
     */
    public function readEx(): string|false
    {
        return fgets($this->pipes[1]);
    }

    /**
     * Чтение данных из процесса
     * Данные будут расшифрованы в base64
     * 
     * @return string|false             Строка с данными или false в случае ошибки
     */
    public function read(): string|false
    {
        return base64_decode($this->readEx());
    }

    /**
     * Ожидание завершения процесса
     */
    public function wait(): void
    {
        while ($this->status()['running'])
            continue;
    }

    /**
     * Принудительное завершение процесса
     * 
     * @param int $signal           Сигнал завершения процесса. По умолчанию SIGTERM
     * 
     * @return bool                 Статус прекращения процесса
     */
    public function destroy(int $signal = 15): bool
    {
        $this->closed = true;
        $this->_rmenv();
        
        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        return proc_terminate($this->proc, $signal);
    }
    
    /**
     * Завершение процесса
     * 
     * @param int $seconds          Лимит времени ожидания
     * @param int $signal           Если процесс превысил лимит ожидания, то будет принудительно завершён с этим сигналом
     * 
     * @return bool                 true если процесс завершён, false если завершён принудительно
     */
    public function close(int $seconds = 0, int $signal = 15): bool
    {
        if ($seconds)
        {
            sleep($seconds);
            
            if ($this->status()['running'])
            {
                $this->destroy($signal);
                return false;
            }
        }
        
        $this->closed = true;
        $this->_rmenv();

        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        proc_close($this->proc);
        return true;
    }

    /**
     * Удаление кэша переменных среды
     */
    private function _rmenv()
    {
        $file = __DIR__.'/env/'.$this->status()['pid'];
        if (file_exists($file))
            unlink($file);
    }
}

spl_autoload_register(function (string $class): void
{
    if (substr($class, 0, 2) === 'VM')
    {
        $file = __DIR__.'/run/'.$class.'/'.$class.'.php';
        if (file_exists($file))
            require($file);
    }
});
