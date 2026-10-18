<?php

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
require dirname(__DIR__).'/system/helpers/func_main.php';
date_default_timezone_set('UTC');
$timestamp = 1792332300;
$format = '%B / %b %d %Y, %H:%M / %A';
if (PHP_VERSION_ID < 80100) {
    // Before PHP 8.1, strftime() is not deprecated and must keep language-pack locales.
    $localized = FALSE;
    foreach (array('de_DE.UTF-8', 'de_DE', 'de-DE', 'German_Germany.1252') as $locale) {
        if (setlocale(LC_ALL, $locale)) { $localized = TRUE; break; }
    }
    if (format_date($format, $timestamp) !== strftime($format, $timestamp)) {
        throw new RuntimeException('Dates must use the active strftime() locale before PHP 8.1.');
    }
    if ($localized && format_date('%B', $timestamp) === 'October') {
        throw new RuntimeException('A non-English locale must not fall back to English month names.');
    }
    setlocale(LC_ALL, 'C');
    $result = $localized ? 'native localized strftime() output' : 'native strftime() output (no German locale available)';
} else {
    if (format_date($format, $timestamp) !== 'October / Oct 18 2026, 14:05 / Sunday') {
        throw new RuntimeException('PHP 8.1+ must render the bundled English formats without strftime().');
    }
    $result = 'English formatting without deprecated strftime()';
}
restore_error_handler();
echo "PASS: $result on PHP ".PHP_VERSION.".\n";
