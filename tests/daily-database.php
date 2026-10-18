<?php

$repo_root = dirname(__DIR__);
$fixture = json_decode(file_get_contents($repo_root.'/.local/installer-tests/latest.json'));
if (!preg_match('/^sharetronix_test_[0-9a-f_]+$/', $fixture->database)) {
    throw new RuntimeException('Expected a disposable installer database.');
}
$credentials = json_decode(file_get_contents($repo_root.'/.local/credentials.json'));
$C = (object) array('DEBUG_MODE' => TRUE, 'DB_MYEXT' => 'mysqli', 'DB_NAME' => $fixture->database);
require_once $repo_root.'/system/classes/class_mysql.php';
$db1 = new mysql('127.0.0.1:33062', $credentials->user, $credentials->password, $fixture->database);
$completed = FALSE;
register_shutdown_function(function () use (&$completed) {
    if (!$completed) { exit(1); }
});
$before = $db1->fetch_field('SELECT COUNT(*) FROM posts');
require $repo_root.'/system/cronjobs/php_24h/1-db_optimize.php';
if ($db1->fetch_field('SELECT COUNT(*) FROM posts') !== $before) {
    throw new RuntimeException('Daily database maintenance must preserve the current post rows.');
}
$completed = TRUE;
echo "PASS: daily database maintenance completes against the current schema and retains posts.\n";
