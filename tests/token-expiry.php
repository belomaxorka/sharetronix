<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$credentials = json_decode(file_get_contents(dirname(__DIR__).'/.local/credentials.json'));
if ($credentials->database !== 'sharetronix_local') {
    throw new RuntimeException('Expected the isolated local test database.');
}
$connection = new mysqli('127.0.0.1', $credentials->user, $credentials->password, $credentials->database, 33062);
// A connection-local temporary table shadows the real table without modifying its rows.
$connection->query('CREATE TEMPORARY TABLE oauth_request_token (id INT PRIMARY KEY, time_stamp BIGINT NOT NULL)');
$connection->query('INSERT INTO oauth_request_token VALUES (1, '.(time()-2000).'), (2, '.(time()-100).')');
class RegressionTokenExpiryDatabase
{
    public $connection;

    public function query($sql)
    {
        return $this->connection->query($sql);
    }
}
$db2 = new RegressionTokenExpiryDatabase();
$db2->connection = $connection;
require dirname(__DIR__).'/system/cronjobs/php_6h/7-clean_request_tokens.php';
$remaining = $connection->query('SELECT id FROM oauth_request_token ORDER BY id')->fetch_all(MYSQLI_NUM);
if ($remaining !== array(array('2'))) {
    throw new RuntimeException('Only the unexpired request token must remain.');
}
$connection->close();
echo "PASS: actual MySQL token cleanup deletes expired rows and retains recent rows.\n";
