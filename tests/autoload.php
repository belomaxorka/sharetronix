<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function regression_previous_autoload($class_name)
{
    if ($class_name === 'RegressionPrevious') {
        class RegressionPrevious {}
    }
}
spl_autoload_register('regression_previous_autoload');
require_once dirname(__DIR__).'/system/helpers/func_main.php';
$C = (object) array('INCPATH' => __DIR__.'/fixtures/autoload/');

foreach (array('RegressionPrevious', 'RegressionCore', 'RegressionLibrary') as $class_name) {
    if (!class_exists($class_name)) {
        throw new RuntimeException('Class could not be loaded: '.$class_name);
    }
}
if (class_exists('RegressionMissing')) {
    throw new RuntimeException('Missing classes must not be manufactured by the loader.');
}
restore_error_handler();
echo "PASS: core and library loading, existing autoloaders, and missing classes without diagnostics.\n";
