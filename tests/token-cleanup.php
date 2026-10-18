<?php

class RegressionTokenCleanupDatabase
{
    public $queries = array();

    public function query($sql)
    {
        $this->queries[] = $sql;
        return TRUE;
    }
}

$db2 = new RegressionTokenCleanupDatabase();
require dirname(__DIR__).'/system/cronjobs/php_6h/7-clean_request_tokens.php';
if (count($db2->queries) !== 1 || strpos($db2->queries[0], 'DELETE FROM oauth_request_token ') !== 0) {
    throw new RuntimeException('Token cleanup must still execute its database maintenance.');
}
echo "PASS: token cleanup runs without a cURL extension or external version-check request.\n";
