<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/constants.php';

error_reporting(E_ALL);

$cmd = escapeshellarg(PHP_BINARY);
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
