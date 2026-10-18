<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/helpers/func_main.php';
$C = (object) array('INCPATH' => dirname(__DIR__).'/system/');
class RegressionPollingPage
{
    public function param($name) { return $name === 'tab' ? 'all' : FALSE; }
}
class RegressionPollingDatabase
{
    public $query;
    public function fetch_field($query) { $this->query = $query; return 2; }
}
$page = new RegressionPollingPage();
$user = (object) array('id' => 7);
$network = $plugins_manager = new stdClass();
$db2 = new RegressionPollingDatabase();
$activity = new activityDashboard();
foreach (array('42tail' => 42, '51' => 51) as $input => $id) {
    $activity->setNewetsPost($input);
    if ($activity->checkNewPosts() !== 2 || strpos($db2->query, 'p.id>'.$id) === FALSE || strpos($db2->query, 'b.user_id="7"') === FALSE || strpos($db2->query, 'p.user_id<>"7"') === FALSE) {
        throw new RuntimeException('Polling must advance its numeric cursor and retain current-user filtering.');
    }
}
restore_error_handler();
echo "PASS: feed polling advances its cursor and preserves user filtering without diagnostics.\n";
