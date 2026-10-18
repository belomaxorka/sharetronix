<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$repo_root = dirname(__DIR__);
$credentials_file = $repo_root.'/.local/credentials.json';
$credentials = json_decode(file_get_contents($credentials_file));
if (!$credentials || $credentials->database !== 'sharetronix_local') {
    throw new RuntimeException('Start the local stack and complete installation first.');
}

$C = (object) array('DEBUG_MODE' => FALSE, 'DB_MYEXT' => 'mysqli');
require_once $repo_root.'/system/classes/class_mysql.php';
require_once $repo_root.'/system/helpers/func_signup.php';
$db1 = $db2 = new mysql('127.0.0.1:33062', $credentials->user, $credentials->password, $credentials->database);
if (!isset($credentials->users)) {
    $credentials->users = new stdClass;
}

foreach (array('qausera', 'qauserb') as $username) {
    if (!isset($credentials->users->$username)) {
        $credentials->users->$username = bin2hex(random_bytes(18));
    }
    $existing = $db2->fetch('SELECT id, email, password FROM users WHERE username="'.$username.'" LIMIT 1');
    if ($existing) {
        if ($existing->email !== $username.'@localhost.test' || $existing->password !== md5($credentials->users->$username)) {
            throw new RuntimeException('Refusing to overwrite an existing account: '.$username);
        }
        continue;
    }
    // Same initial account fields as signup.php; registration itself is tested separately.
    $db2->query('INSERT INTO users SET email="'.$username.'@localhost.test", username="'.$username.'", password="'.md5($credentials->users->$username).'", fullname="Baseline '.$username.'", language="en", timezone="UTC", reg_date="'.time().'", reg_ip="2130706433", active=1, is_network_admin=0');
    $user_id = (int) $db2->insert_id();
    if (!$user_id) {
        throw new RuntimeException('Could not create the fixture account.');
    }
    set_user_default_notification_rules($user_id);
}

file_put_contents($credentials_file, json_encode($credentials, JSON_PRETTY_PRINT)."\n");
echo "Local fixture users are ready: qausera and qauserb. Passwords are in .local/credentials.json.\n";
