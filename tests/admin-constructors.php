<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
require __DIR__.'/fixtures/class-declaration.php';
require dirname(__DIR__).'/system/classes/class_AdminPlugins.php';
regression_load_class(dirname(__DIR__).'/system/controllers/admin_languages.php', 'AdminLanguages');
class RegressionAdminPlugins extends AdminPlugins
{
    public function state() { return array($this->user, $this->tpl, $this->params, $this->db); }
}
class RegressionAdminLanguages extends AdminLanguages
{
    public function state() { return array($this->user, $this->tpl, $this->params, $this->db); }
}
$db1 = $page = $D = new stdClass();
foreach (array('RegressionAdminPlugins','RegressionAdminLanguages') as $class) {
    $user = (object) array('id' => 7);
    $tpl = (object) array('name' => 'first');
    $params = (object) array('tab' => 'first');
    $controller = new $class($user, $tpl, $params);
    if ($controller->state() !== array($user, $tpl, $params, $db1)) { throw new RuntimeException('Initialize administrator controller state.'); }
    $tpl = (object) array('name' => 'second');
    $params = (object) array('tab' => 'second');
    if ($controller->state()[1] !== $tpl || $controller->state()[2] !== $params) { throw new RuntimeException('Preserve template/parameter variable references.'); }
}
restore_error_handler();
echo "PASS: administrator constructors initialize state and preserve template/parameter references offline.\n";
