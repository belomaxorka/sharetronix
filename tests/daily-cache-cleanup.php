<?php

$root = dirname(__DIR__).'/.local/daily-cache-tests/'.uniqid('', TRUE);
mkdir($root.'/tmp/nested', 0777, TRUE);
mkdir($root.'/working');
foreach (array('0', 'cache.tmp', '.gitkeep') as $file) {
    file_put_contents($root.'/tmp/'.$file, 'temporary fixture');
    file_put_contents($root.'/working/'.$file, 'working-directory sentinel');
}
$C = (object) array('INCPATH' => $root.'/', 'TMP_DIR' => $root.'/tmp/');
$invalidated = FALSE;
function invalidateCachedHTML()
{
    $GLOBALS['invalidated'] = TRUE;
}
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$working_directory = getcwd();
chdir($root.'/working');
try {
    require dirname(__DIR__).'/system/cronjobs/php_24h/3-clear_html_cache.php';
} finally {
    chdir($working_directory);
    restore_error_handler();
}
clearstatcache();
foreach (array('0', 'cache.tmp') as $file) {
    if (file_exists($root.'/tmp/'.$file)) {
        throw new RuntimeException('Daily cleanup must remove files from the private temporary directory.');
    }
}
foreach (array('0', 'cache.tmp', '.gitkeep') as $file) {
    if (file_get_contents($root.'/working/'.$file) !== 'working-directory sentinel') {
        throw new RuntimeException('Daily cleanup must preserve files in its working directory.');
    }
}
if (!$invalidated || !is_file($root.'/tmp/.gitkeep') || !is_dir($root.'/tmp/nested')) {
    throw new RuntimeException('Invalidate HTML while retaining placeholders and subdirectories.');
}
echo "PASS: daily cache cleanup targets the private directory and preserves unrelated files.\n";
