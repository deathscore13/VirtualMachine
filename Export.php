<?php

abstract class VMExport
{
    public const DATA_ARRAY     = 0; /**< Данные в представлении массива */
    public const DATA_BOOL      = 1; /**< Данные типа bool */
    public const DATA_NULL      = 2; /**< null */
    public const DATA_CLASS     = 3; /**< Данные в представлении класса */
    public const DATA_FLOAT     = 4; /**< Число с плавающей запятой */
    public const DATA_INT       = 5; /**< Целое число */
    public const DATA_STRING    = 6; /**< Строка */

    /**
     * Экспортирование переменной или статического класса
     * 
     * @param mixed $value              Значение переменной или имя статического класса
     * @param bool $is_static           Если true, то будет экспортирован статический класс, а не строка
     * 
     * @return string                   Значение пригодное для экспортирования
     */
    public static function get(mixed $value, bool $is_static = false): string
    {
        if (is_array($value))
            return self::DATA_ARRAY.json_encode($value);
        
        if (is_bool($value))
            return self::DATA_BOOL.($value ? '1' : '0');
        
        if (is_null($value))
            return self::DATA_NULL.'null';
        
        if (is_object($value))
            return self::DATA_CLASS.get_class($value).'@'.json_encode(self::class((new ReflectionClass($value)), $value));
        
        if ($is_static)
        {
            if (!class_exists($value, false))
                throw new Exception('class '.$value.' not found');

            return self::DATA_CLASS.$value.'@'.json_encode(self::class((new ReflectionClass($value)), null));
        }

        if (is_float($value))
            return self::DATA_FLOAT.$value;

        if (is_int($value))
            return self::DATA_INT.$value;
        
        if (is_string($value))
            return self::DATA_STRING.$value;
        
        throw new Exception('unsupported data type');
    }

    /**
     * Экспорт класса
     * 
     * @param ReflectionClass $class    Объект ReflectionClass
     * @param ?object $obj              Объект класса или null, если класс статический
     * 
     * @return array                    Массив вида ['props' => [...], 'parent' => [...]]
     */
    public static function class(ReflectionClass $class, ?object $obj): array
    {
        $props = [];
        foreach ($class->getProperties() as $prop)
        {
            $prop->setAccessible(true);
            $props['props'][$prop->name] = self::get($prop->getValue($obj));
        }

        $class = $class->getParentClass();
        if ($class !== false)
            $props['parent'] = self::class($class, $obj);

        return $props;
    }
}
