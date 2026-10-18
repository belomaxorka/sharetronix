<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
class RegressionActivityPage
{
    public function load_langfile($path) {}
}
class RegressionActivityDatabase
{
    public $query;
    public function query($query) { $this->query = $query; return $query; }
    public function fetch_object($result) { return (object) array('id' => 17); }
}
class newpost
{
    public function set_message($message) {}
    public function save() { return $GLOBALS['regression_result']; }
    public function remove_post_cache() {}
}
class RegressionActivityRoutine
{
    public function load() {}
    public function useActivityContainer($part, $flag) {}
}
class template
{
    public $routine;
    public $staticHTML;
    private $item;
    public function __construct($params, $default) { $this->routine = $this->staticHTML = new RegressionActivityRoutine(); }
    public function useStaticHTML() {}
    public function initRoutine($routine, $params) { $this->item = $params[0]; }
    public function display($return) { return $this->item->id.':'.$this->item->type; }
}
$C = (object) array('POST_MAX_SYMBOLS' => 0);
$page = new RegressionActivityPage();
$db2 = new RegressionActivityDatabase();
$network = $plugins_manager = new stdClass();
$ajax_action = 'set';
foreach (array('public' => 'posts', 'private' => 'posts_pr') as $kind => $table) {
    $user = (object) array('sess' => array());
    $regression_result = '17_'.$kind;
    $_POST = array('activities_text' => 'Fixture message', 'token' => 'regression-'.$kind);
    ob_start();
    try { require dirname(__DIR__).'/system/controllers/ajax/activities.php'; } finally { $body = ob_get_clean(); }
    $response = json_decode($body);
    if (!$response || $response->inserted_activities_id !== 17 || $response->html !== '17:'.$kind || strpos($db2->query, 'FROM '.$table.' WHERE') === FALSE) {
        throw new RuntimeException('Use the saved post ID and type for its table and response.');
    }
}
restore_error_handler();
echo "PASS: the activity handler preserves public/private saved-result types and IDs without diagnostics.\n";
