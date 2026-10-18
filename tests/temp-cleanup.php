<?php

$repo_root = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = 'localhost:8092';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once $repo_root.'/system/helpers/func_main.php';
require_once $repo_root.'/.local/site/system/conf_system.php';
if (!isset($C->TMP_DIR) || $C->TMP_DIR !== $C->INCPATH.'tmp/') {
    throw new RuntimeException('The application must define its private temporary directory.');
}
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$directory = $repo_root.'/.local/temp-cleanup-tests/'.uniqid('', TRUE).'/';
mkdir($directory, 0777, TRUE);
foreach (array('0', 'expired.tmp', 'recent.tmp', '.gitkeep') as $file) {
    file_put_contents($directory.$file, 'synthetic temporary data');
    $time = $file === 'recent.tmp' ? time() : time()-7200;
    touch($directory.$file, $time, $time);
}
mkdir($directory.'nested');
$C->TMP_DIR = $directory;
require $repo_root.'/system/cronjobs/php_6h/2-clean_temp_dir.php';
clearstatcache();
if (file_exists($directory.'0') || file_exists($directory.'expired.tmp')) {
    throw new RuntimeException('Expired files must be removed, including a file named zero.');
}
if (!file_exists($directory.'recent.tmp') || !file_exists($directory.'.gitkeep') || !is_dir($directory.'nested')) {
    throw new RuntimeException('Recent files, directory placeholders, and subdirectories must be retained.');
}
restore_error_handler();
echo "PASS: private temporary directory and age-based file cleanup without diagnostics.\n";
