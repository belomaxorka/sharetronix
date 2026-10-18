<?php

require_once dirname(__DIR__).'/system/helpers/func_main.php';

$C = (object) array('RNDKEY' => 'regression', 'DOMAIN' => '');
$cases = array(
    'example.test' => array('regressionexampletest', '.example.test'),
    'example.test:8092' => array('regressionexampletest8092', '.example.test'),
    'm.example.test:8092' => array('regressionmexampletest8092', '.example.test'),
    'localhost:8092' => array('regressionlocalhost8092', ''),
    '127.0.0.1:8092' => array('regression1270018092', '127.0.0.1'),
    '[::1]:8092' => array('regression18092', ''),
    '[::ffff:127.0.0.1]:8092' => array('regressionffff1270018092', '')
);

foreach ($cases as $domain => $expected) {
    $C->DOMAIN = $domain;
    $name = my_session_name($domain);
    if ($name !== $expected[0] || cookie_domain() !== $expected[1]) {
        throw new RuntimeException('Incorrect session or cookie scope for '.$domain);
    }
    if (!preg_match('/^[a-z0-9]+$/i', $name) || !preg_match('/[a-z]/i', $name)) {
        throw new RuntimeException('Session name is not safe for cookies.');
    }
}

if (my_session_name('localhost:8092') === my_session_name('localhost:8093')) {
    throw new RuntimeException('Different application ports must retain separate session names.');
}
$C->RNDKEY = '12345';
if (!preg_match('/[a-z]/i', my_session_name('127.0.0.1'))) {
    throw new RuntimeException('An IPv4 host and numeric installation key still need a session-name letter.');
}
echo "PASS: session names and cookie scopes, including ports and IP literals.\n";
