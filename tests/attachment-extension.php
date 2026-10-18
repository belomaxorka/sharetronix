<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/helpers/func_main.php';
$C = (object) array();

// Allowed document/image/media extensions are accepted (lowercased, last extension only).
$allowed = array(
    'photo.jpg' => 'jpg',
    'PHOTO.JPG' => 'jpg',
    'archive.tar.gz' => 'gz',
    'report.final.PDF' => 'pdf',
    'clip.mp4' => 'mp4',
    'note.txt' => 'txt',
);
foreach ($allowed as $name => $ext) {
    if (safe_attachment_extension($name) !== $ext) {
        throw new RuntimeException('Expected '.$ext.' for '.$name);
    }
}

// Script/markup and extension-less names are rejected.
$rejected = array(
    'shell.php', 'shell.PHP', 'shell.pht', 'shell.phtml', 'shell.phar', 'x.php5',
    'app.cgi', 'app.pl', 'app.py', 'page.html', 'page.htm', 'vector.svg', 'doc.xml',
    'shell.php.', 'noextension', '.', 'trailing.php ',
);
foreach ($rejected as $name) {
    if (safe_attachment_extension($name) !== FALSE) {
        throw new RuntimeException('Expected rejection for '.var_export($name, TRUE));
    }
}

// Double extension keeps only the safe trailing one.
if (safe_attachment_extension('shell.php.jpg') !== 'jpg') {
    throw new RuntimeException('Double extension must resolve to the final extension.');
}

// A custom allowlist overrides the default.
$C->ATTACH_ALLOWED_EXTENSIONS = array('jpg');
if (safe_attachment_extension('note.txt') !== FALSE || safe_attachment_extension('photo.jpg') !== 'jpg') {
    throw new RuntimeException('Configured allowlist must be honored.');
}

restore_error_handler();
echo "PASS: attachment extension allowlist accepts safe files and rejects script/markup uploads.\n";
