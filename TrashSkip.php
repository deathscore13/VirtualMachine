<?php

abstract class VMTrashSkip
{
    public const TRASH_PREFIX = '__VM_TRASH_POINTER__';

    /**
     * Установка метки в дочернем процессе для пропуска мусора
     * 
     * @param VMWrite $write            Объект VMWrite
     * @param ?string $unique           Уникальное значение
     */
    public static function child(VMWrite $write, ?string $unique = null): void
    {
        if (!$unique)
            $unique = self::TRASH_PREFIX.getmypid();
        
        $write->writeEx($unique);
    }

    /**
     * Пропуск вывода мусора после запуска дочернего процесса
     * 
     * @param VirtualMachine $vm        Объект VirtualMachine
     * @param ?string $unique           Уникальное значение
     */
    public static function parent(VirtualMachine $vm, ?string $unique = null): void
    {
        if (!$unique)
            $unique = self::TRASH_PREFIX.$vm->status()['pid'];
        
        while (($res = $vm->readEx()) !== false && strpos($res, $unique) === -1)
            continue;
    }
}
