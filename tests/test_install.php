<?php
//
// After including cdash_test_case.php, subsequent require_once calls are
// relative to the top of the CDash source tree
//
require_once dirname(__FILE__) . '/cdash_test_case.php';

class InstallTestCase extends KWWebTestCase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function testInstall()
    {
        $this->pass('Passed');
    }
}
