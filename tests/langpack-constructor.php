<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$C = (object) array('PROJPATH' => dirname(__DIR__).'/', 'INCPATH' => dirname(__DIR__).'/system/', 'STX_USERNAME' => 'fixture', 'STX_PASSWORD' => 'fixture');
require dirname(__DIR__).'/system/classes/class_Langpack.php';
$data = (object) array('abrv' => 'en', 'lang_name' => 'English', 'version' => 3);
$pack = new Langpack($data, FALSE);
$state = $pack->toArray();
if ($state['langkey'] !== 'en' || $state['lang_name'] !== 'English' || $state['version'] !== 3 || $state['folder'] !== $C->PROJPATH.'system/languages/en') {
    throw new RuntimeException('Construct language metadata and its local folder without a network request.');
}
$data->version = 4;
$pack->Langpack($data, FALSE);
if ($pack->version !== 4) {
    throw new RuntimeException('Retain explicit calls to the legacy initialization method.');
}
unset($C->STX_USERNAME);
$rejected = FALSE;
try { new Langpack($data, FALSE); } catch (Exception $error) { $rejected = $error->getMessage() === 'No appstore login data is available'; }
if (!$rejected) { throw new RuntimeException('Retain the existing missing-credentials guard.'); }
restore_error_handler();
echo "PASS: language metadata construction, legacy initialization calls, and the existing guard without network access.\n";
