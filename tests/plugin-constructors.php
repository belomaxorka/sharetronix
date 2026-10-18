<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
require __DIR__.'/fixtures/class-declaration.php';
$root = dirname(__DIR__);
$C = (object) array('INCPATH' => $root.'/system/', 'INSTALLER_PATH' => $root.'/system/plugin_installer/');
foreach (array('FieldOption','FieldOptionDefault','FieldOptionLimit','FieldOptionNull') as $class) { require $C->INSTALLER_PATH.'libs/db/'.$class.'.php'; }
$limit = new FieldOptionLimit('12');
if ($limit->getValue() !== 12 || (new FieldOptionDefault('fixture'))->getValue() !== 'fixture' || (new FieldOptionNull(''))->getValue() !== FALSE) {
    throw new RuntimeException('Initialize plugin field option values and their intended conversions.');
}
$limit->FieldOptionLimit('23');
if ($limit->getValue() !== 23) { throw new RuntimeException('Retain explicit legacy option initialization.'); }
require $C->INSTALLER_PATH.'libs/db/ActiveRecord.php';
$constructor = (new ReflectionClass('ActiveRecord'))->getConstructor();
if (!$constructor || $constructor->getName() !== '__construct' || !$constructor->isPrivate()) { throw new RuntimeException('Retain singleton construction privacy on PHP 8.'); }
class RegressionPluginConnection
{
    public $queries = array();
    public function query($query) { $this->queries[] = $query; return array(); }
}
$connection = new RegressionPluginConnection();
$instance = new ReflectionProperty('ActiveRecord', 'instance');
$instance->setAccessible(TRUE);
$instance->setValue(NULL, $connection);
require $C->INSTALLER_PATH.'libs/db/DatabaseReal.php';
require $C->INSTALLER_PATH.'libs/validation/DatabaseValidate.php';
$database = new DatabaseReal('fixture');
$database->execute('SELECT fixture');
if ($connection->queries !== array('SELECT fixture')) { throw new RuntimeException('The real adapter must retain its connection boundary.'); }
$validator = new DatabaseValidate('fixture');
$validator->execute('SELECT offline');
$messages = $validator->getResult();
if (count($messages) !== 1 || strpos($messages[0]->getMessage(), 'SELECT offline') === FALSE || (new ValidationError('fixture-error'))->getMessage() !== 'fixture-error') {
    throw new RuntimeException('Initialize and retain validation messages.');
}
$owner = new TableOwner();
$ownerDb = new ReflectionProperty('TableOwner', 'db');
$ownerDb->setAccessible(TRUE);
if ($ownerDb->getValue($owner) !== $connection) { throw new RuntimeException('Initialize table ownership with the same singleton connection.'); }
regression_load_class($C->INSTALLER_PATH.'Installer.php', 'Installer', TRUE);
class RegressionPluginInstaller extends Installer
{
    public function up() { $this->db->execute('SELECT installer-fixture'); }
    public function down() {}
}
Installer::$mode = Installer::INSTALLER_MODE_VALIDATION;
Installer::$manifest = (object) array('plugin_name' => 'fixture');
$installer = new RegressionPluginInstaller();
$installer->up();
if (strpos($installer->finish()[0]->getMessage(), 'installer-fixture') === FALSE) { throw new RuntimeException('Initialize the selected validation adapter.'); }
Installer::$mode = NULL;
$rejected = FALSE;
try { new RegressionPluginInstaller(); } catch (Exception $error) { $rejected = strpos($error->getMessage(), 'no install mode set') !== FALSE; }
if (!$rejected) { throw new RuntimeException('Retain the missing-install-mode guard.'); }
restore_error_handler();
echo "PASS: plugin option conversion, validation, adapter initialization, singleton privacy, and legacy calls offline.\n";
