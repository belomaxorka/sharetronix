<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$root = dirname(__DIR__);
require $root.'/system/classes/class_templateElement.php';
require $root.'/system/classes/class_htmlBlock.php';
require $root.'/system/classes/class_layout.php';
require $root.'/system/classes/class_pageDesigner.php';
require $root.'/system/helpers/func_html.php';

foreach (array('htmlBlock', 'layout') as $class) {
    $constructor = new ReflectionMethod($class, '__construct');
    $parameters = $constructor->getParameters();
    if ($constructor->getNumberOfRequiredParameters() !== 3 || !$parameters[2]->isPassedByReference()) {
        throw new RuntimeException('Retain the three-argument constructor and its reference contract.');
    }
}
$designer = new pageDesigner();
$html = $designer->createTableDetailsBlock('Details', array('Name' => '<A>'), 'details', FALSE);
if (strpos($html, '<h3>Details</h3>') === FALSE || strpos($html, '&lt;A&gt;') === FALSE || strpos($html, 'Name:') !== FALSE) {
    throw new RuntimeException('Retain detail-table headings, escaping, and colon selection.');
}
if ($designer->createTableDetailsBlock('', array()) !== '' || createTableDetailsBlock('', array()) !== '') {
    throw new RuntimeException('Empty detail tables must remain empty.');
}
$C = (object) array('SITE_URL' => 'http://localhost.test/');
$html = createTableInput('Edit', array('<tr><td>Fixture</td></tr>'), '', 'settings');
if (strpos($html, 'action="http://localhost.test/settings"') === FALSE || strpos($html, 'Fixture') === FALSE) {
    throw new RuntimeException('Retain form actions and supplied rows.');
}
restore_error_handler();
echo "PASS: template/form signatures load cleanly and retain their argument and rendering contracts.\n";
