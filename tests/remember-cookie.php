<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/helpers/func_main.php';
$C = (object) array('INCPATH' => dirname(__DIR__).'/system/', 'DOMAIN' => 'localhost:8093');
$network = (object) array('id' => 1);
$cache = new stdClass();
class RegressionCookieDatabase
{
    public function query($query) {}
    public function fetch_object() { return (object) array('username' => 'fixture', 'password' => 'fixture-hash', 'email' => 'fixture@localhost.test'); }
}
$db1 = $db2 = new RegressionCookieDatabase();
$_SERVER['HTTP_USER_AGENT'] = 'Sharetronix regression';
$_SESSION = array();
$_COOKIE['rememberme'] = '7_invalid-signature';
$user = new user();
if ($user->try_autologin() !== FALSE || isset($_COOKIE['rememberme']) || $user->is_logged) {
    throw new RuntimeException('Reject and clear an invalid persistent-login cookie.');
}
$user->is_logged = TRUE;
$user->id = 7;
$user->sess = array('IS_LOGGED' => TRUE, 'LOGGED_USER' => (object) array('id' => 7));
$_COOKIE['rememberme'] = '7_old-value';
$user->logout();
if ($user->is_logged || $user->id || isset($user->sess['IS_LOGGED']) || isset($user->sess['LOGGED_USER']) || isset($_COOKIE['rememberme'])) {
    throw new RuntimeException('Logout must clear login state and the persistent-login cookie.');
}
restore_error_handler();
echo "PASS: invalid remember-me cookies and logout are cleared without diagnostics.\n";
