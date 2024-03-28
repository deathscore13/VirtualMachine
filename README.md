# VirtualMachine
### Виртуальная машина для запуска и выполнения действий с программами для PHP 8.0.0+

Советую открыть **`VirtualMachine.php`**, **`Read.php`**, **`Write.php`**, **`Export.php`**, **`Import.php`** и **`TrashSkip.php`**, и почитать описания методов

<br><br>
### Для работы из родительского процесса
* **`VitrualMachine.php`**
* **`Export.php`**
* **`TrashSkip.php`**
* Автоматически подгружаемые оболочки из папки **`run`**

<br><br>
### Для работы из дочернего PHP процесса
* **`Read.php`**
* **`Write.php`**
* **`Export.php`**
* **`TrashSkip.php`**

<br><br>
### Пример использования
**`test.php`**
```php
// подключение класса для вывода данных
require('VirtualMachine/Write.php');

// подключение утилиты для пропуска мусора из вывода
require('VirtualMachine/TrashSkip.php');

// создание объекта VMWrite
$w = new VMWrite();

// установка точки для пропуска мусора из вывода
VMTrashSkip::child($w);

// вывод данных
$w->write('1');

// вывод данных
$w->write('2');
```
**`main.php`**
```php
// подключение VirtualMachine
require('VirtualMachine/VirtualMachine.php');

// подключение утилиты для пропуска мусора из вывода
require('VirtualMachine/TrashSkip.php');

// запуск test.php
// не советую использовать PHP_BINARY, поскольку процесс может запускаться
// через php-fpm, который некорректно работает в CLI режиме
$test = new VitrualMachine('stderr', PHP_BINARY.' test.php');

// пропуск мусора из вывода
VMTrashSkip::parent($test);

// вывод: 1
echo($test->read().PHP_EOL);

// вывод: 2
echo($test->read().PHP_EOL);
```
