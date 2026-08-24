<?php

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);

define('GETTEXT_LANGUAGES_TESTROOTDIR', str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__)));
define('GETTEXT_LANGUAGES_TESTDIR', str_replace(DIRECTORY_SEPARATOR, '/', __DIR__));

$cmd = PHP_BINARY ? escapeshellarg(PHP_BINARY) : 'php';
$exportPluralRules = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'export-plural-rules';
$cmd .= ' ' . escapeshellarg($exportPluralRules);

$execOutput = [];
$rc = -1;
exec($cmd . ' php ' . escapeshellarg('--output=' . GETTEXT_LANGUAGES_TESTDIR . '/data.php'), $execOutput, $rc);
if ($rc !== 0) {
    throw new Exception(implode("\n", $execOutput));
}

exec($cmd . ' json ' . escapeshellarg('--output=' . GETTEXT_LANGUAGES_TESTDIR . '/data.json'), $execOutput, $rc);
if ($rc !== 0) {
    throw new Exception(implode("\n", $execOutput));
}

require_once dirname(__DIR__) . '/src/Languages/autoloader.php';
