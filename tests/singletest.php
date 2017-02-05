<?php
//
// After including cdash_test_case.php, subsequent require_once calls are
// relative to the top of the CDash source tree
//
require_once dirname(__FILE__) . '/cdash_test_case.php';

require_once 'tests/kwtest/kw_test_manager.php';

$logfilename = $CDASH_LOG_FILE;

global $CDASH_DB_NAME, $CDASH_SVNROOT;
putenv('CDASH_DB_NAME=' . $CDASH_DB_NAME);
$command = sprintf(
    'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
    '0.0.0.0',
    getenv('CDASH_SERVER_PORT'),
    '/var/www/CDash/public'
);

// Execute the command and store the process ID
$output = array();
exec($command, $output);
$pid = (int) $output[0];

echo sprintf(
    '%s - Web server started on %s:%d with PID %d',
    date('r'),
    '0.0.0.0',
    getenv('CDASH_SERVER_PORT'),
    $pid
) . PHP_EOL;

// Kill the web server when the process ends
register_shutdown_function(function() use ($pid) {
    echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
    exec('kill ' . $pid);
});


$manager = new HtmlTestManager();
$manager->removeLogAndBackupFiles($logfilename);
//$manager->setTestDirectory(getcwd());
$manager->setDatabase($db);
$manager->runFileTest(new TextReporter(), $argv[1]);
