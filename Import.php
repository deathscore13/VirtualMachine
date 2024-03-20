<?php

abstract class VMImport
{
    public const DATA_ARRAY     = 0; /**< Данные в представлении массива */
    public const DATA_BOOL      = 1; /**< Данные типа bool */
    public const DATA_NULL      = 2; /**< null */
    public const DATA_CLASS     = 3; /**< Данные в представлении класса */
    public const DATA_FLOAT     = 4; /**< Число с плавающей запятой */
    public const DATA_INT       = 5; /**< Целое число */
    public const DATA_STRING    = 6; /**< Строка */

    /**
     * Импортирование переменной или статического класса
     * 
     * @param string $name              Имя алиаса
     * @param bool $is_static           Если true, то будет импортирован статический класс, а не строка
     * 
     * @return bool                     true при успехе, false если алиас не найден
     */
    public static function get(string $name, bool $is_static = false): bool
    {
        static $env = [];
        if (!$env)
        {
            $fenv = fopen(__DIR__.'/env/'.getmypid(), 'rb');
            $env = json_decode(fgets($fenv), true);
            fclose($fenv);
        }

        if (isset($env[$name]))
        {
            if ($is_static)
                self::value($env[$name], $is_static);
            else
                $GLOBALS[$name] = self::value($env[$name], $is_static);

            return true;
        }
        return false;
    }

    /**
     * Получение значения из буфера после экспорта
     * 
     * @param string $buffer            Буфер
     * @param bool $is_static           Если true, то будет импортирован статический класс, а не строка
     * 
     * @return mixed                    Значение которое можно использовать в PHP коде
     */
    public static function value(string $buffer, bool $is_static = false): mixed
    {
        switch ($buffer[0])
        {
            case self::DATA_ARRAY:
            {
                return json_decode(substr($buffer, 1), true);
            }
            case self::DATA_BOOL:
            {
                return ($buffer[1] == '1' ? true : false);
            }
            case self::DATA_NULL:
            {
                return null;
            }
            case self::DATA_CLASS:
            {
                $pos = strpos($buffer, '@');
                return self::class(new ReflectionClass(substr($buffer, 1, $pos-1)), json_decode(substr($buffer, $pos+1), true), $is_static);
            }
            case self::DATA_FLOAT:
            {
                return (float)substr($buffer, 1);
            }
            case self::DATA_INT:
            {
                return (int)substr($buffer, 1);
            }
            case self::DATA_STRING:
            {
                return substr($buffer, 1);
            }
            default:
            {
                throw new Exception('unsupported data type');
            }
        }
        return null; // good practice
    }

    /**
     * Импорт класса
     * 
     * @param ReflectionClass $class    Объект ReflectionClass
     * @param array $props              Массив с данными в виде ['props' => [...], 'parent' => [...]]
     * @param bool $is_static           Если true, то будет импортирован статический класс, а не строка
     * @param ?object $obj              Уже определённый объект класса
     * 
     * @return ?object                  Импортированный объект класса или null, если класс статический
     */
    public static function class(ReflectionClass $class, array $props, bool $is_static, ?object $obj = null): ?object
    {
        if (!$is_static && !$obj)
            $obj = $class->newInstanceWithoutConstructor();

        foreach ($props['props'] as $name => $value)
        {
            $prop = $class->getProperty($name);
            $prop->setAccessible(true);

            $value = self::value($value);

            if ($prop->isStatic())
                @$prop->setValue($value); // warning in version 8.3.0+
            else
                $prop->setValue($obj, $value);
        }

        $class = $class->getParentClass();
        if ($class !== false)
            self::class($class, $props['parent'], false, $obj);

        return $obj;
    }
}
