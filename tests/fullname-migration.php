<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$repo_root = dirname(__DIR__);
// A disposable stand passes its connection through the environment; otherwise use the documented local stack.
$host = getenv('STX_TEST_DB_HOST');
$port = getenv('STX_TEST_DB_PORT');
$user = getenv('STX_TEST_DB_USER');
$password = getenv('STX_TEST_DB_PASSWORD');
$database = getenv('STX_TEST_DB_NAME');
if ($user === FALSE || $database === FALSE) {
    $credentials = json_decode(file_get_contents($repo_root.'/.local/credentials.json'));
    if ($credentials->database !== 'sharetronix_local') {
        throw new RuntimeException('Expected the isolated local test database.');
    }
    $host = '127.0.0.1';
    $port = 33062;
    $user = $credentials->user;
    $password = $credentials->password;
    $database = $credentials->database;
}
$connection = new mysqli($host === FALSE || $host === '' ? '127.0.0.1' : $host, $user, (string) $password, $database, (int) ($port ? $port : 3306));
$connection->set_charset('utf8');
// Connection-local temporary tables shadow the real tables without modifying their rows.
$connection->query('CREATE TEMPORARY TABLE users (id INT PRIMARY KEY, fullname VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL)');
$connection->query('CREATE TEMPORARY TABLE settings (word VARCHAR(100) NOT NULL, value TEXT NOT NULL, UNIQUE KEY word (word))');
$connection->query("INSERT INTO settings VALUES ('VERSION', '3.6.2')");

// id => array(stored before the update, expected after it)
$cases = array(
    1 => array('"><b>marker</b>', '&quot;&gt;&lt;b&gt;marker&lt;/b&gt;'), // raw row from self-registration before the fix
    2 => array('&lt;b&gt;marker&lt;/b&gt;', '&lt;b&gt;marker&lt;/b&gt;'),   // already escaped by the settings/API paths
    3 => array('Tom & Jerry', 'Tom &amp; Jerry'),                            // bare ampersand only exists in raw rows
    4 => array('A &amp; B', 'A &amp; B'),
    5 => array('Alice', 'Alice'),
    6 => array("O'Brien", "O'Brien"),                                         // apostrophes are not part of the convention
    7 => array('Ünïcødé <x>', 'Ünïcødé &lt;x&gt;'),                          // multibyte text survives REPLACE()
    8 => array('&#039;quoted&#039;', '&#039;quoted&#039;'),
    9 => array('R&D &amp; Co', 'R&D &amp; Co'),                               // ambiguous mix is left alone
    10 => array('', ''),
);
$insert = $connection->prepare('INSERT INTO users (id, fullname) VALUES (?, ?)');
foreach ($cases as $id => $case) {
    $insert->bind_param('is', $id, $case[0]);
    $insert->execute();
}

// Run exactly the statements the updater ships for this version.
require $repo_root.'/install/include/descriptions/database-tables-update.php';
if (!isset($db_updates['3.6.3']['queries'])) {
    throw new RuntimeException('The 3.6.3 updater entry must exist.');
}
$run = function () use ($connection, $db_updates) {
    foreach ($db_updates['3.6.3']['tables'] as $sql) {
        $connection->query($sql);
    }
    foreach ($db_updates['3.6.3']['queries'] as $sql) {
        $connection->query($sql);
    }
};
$check = function ($label) use ($connection, $cases) {
    $rows = $connection->query('SELECT id, fullname FROM users ORDER BY id')->fetch_all(MYSQLI_NUM);
    if (count($rows) !== count($cases)) {
        throw new RuntimeException($label.': the update must not add or remove rows.');
    }
    foreach ($rows as $row) {
        $expected = $cases[(int) $row[0]][1];
        if ($row[1] !== $expected) {
            throw new RuntimeException($label.': row '.$row[0].' expected '.var_export($expected, TRUE).', got '.var_export($row[1], TRUE));
        }
    }
};
$run();
$check('first run');
$run();
$check('second run');
$version = $connection->query("SELECT COUNT(*), MAX(value) FROM settings WHERE word='VERSION'")->fetch_row();
if ($version !== array('1', '3.6.3')) {
    throw new RuntimeException('The updater must replace the existing VERSION row instead of failing on the unique key.');
}
$connection->close();
echo "PASS: the 3.6.3 updater escapes raw display names once, keeps escaped names, and can be repeated.\n";
