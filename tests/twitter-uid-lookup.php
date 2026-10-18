<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/classes/class_user.php';

// Database double: every account without a linked Twitter id has twitter_uid="".
class RegressionTwitterLookupDatabase
{
    public $queries = array();

    public function query($sql, $remember_result = TRUE)
    {
        $this->queries[] = $sql;
        return count($this->queries);
    }

    public function fetch_object($result = FALSE)
    {
        $sql = end($this->queries);
        if (preg_match('/twitter_uid="([^"]*)"/', $sql, $m) && ($m[1] === '' || $m[1] === '12345')) {
            return (object) array('id' => $m[1] === '' ? 1 : 7, 'username' => $m[1] === '' ? 'admin' : 'linked');
        }
        return FALSE;
    }

    public function e($string)
    {
        return addslashes($string);
    }
}

$user = (new ReflectionClass('user'))->newInstanceWithoutConstructor();
$user->db2 = new RegressionTwitterLookupDatabase();

foreach (array('', NULL, ' ', 'abc', '12a', '1" OR "1"="1', '-5') as $uid) {
    if ($user->get_user_by_twitter_uid($uid) !== FALSE) {
        throw new RuntimeException('A missing or malformed Twitter id must not match an account: '.var_export($uid, TRUE));
    }
}
if (count($user->db2->queries) !== 0) {
    throw new RuntimeException('A missing or malformed Twitter id must not reach the database.');
}

$linked = $user->get_user_by_twitter_uid('12345');
if (!is_object($linked) || $linked->id !== 7) {
    throw new RuntimeException('A real linked Twitter id must still find its account.');
}
if ($user->get_user_by_twitter_uid(12345) === FALSE) {
    throw new RuntimeException('Numeric ids from the Twitter profile must still be accepted.');
}

restore_error_handler();
echo "PASS: Twitter id lookups reject missing/malformed ids and still find linked accounts.\n";
