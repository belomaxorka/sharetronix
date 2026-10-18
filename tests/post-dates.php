<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/helpers/func_main.php';
require dirname(__DIR__).'/system/classes/class_post.php';
date_default_timezone_set('UTC');
setlocale(LC_TIME, 'C');
$timestamp = 1704164645;
$formats = array(
    '%b %d %Y, %H:%M' => 'Jan 02 2024, 03:04',
    '%B %d, %Y' => 'January 02, 2024',
    '%Y-%m-%d %H:%M:%S %%' => '2024-01-02 03:04:05 %',
    '%a %A %I:%M %p' => 'Tue Tuesday 03:04 AM',
    '%j %u %U %V %w %W %y' => '002 2 00 01 2 01 24',
    '%c | %x %X' => 'Tue Jan  2 03:04:05 2024 | 01/02/24 03:04:05'
);
foreach ($formats as $format => $expected) {
    if (post::parse_date($timestamp, FALSE, $format) !== $expected || post::parse_date($timestamp, 'auto', $format) !== $expected) {
        throw new RuntimeException('Retain the bundled English date formats and old-post fallback.');
    }
}
date_default_timezone_set('America/New_York');
if (post::parse_date($timestamp, FALSE) !== 'Jan 01 2024, 22:04') {
    throw new RuntimeException('Date rendering must retain the selected PHP timezone.');
}
restore_error_handler();
echo "PASS: bundled/custom English date formats, literal percent signs, old-post fallback, and timezone behavior.\n";
