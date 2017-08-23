<?php
require_once dirname(__FILE__) . '/cdash_test_case.php';
require_once 'include/common.php';
require_once 'include/pdo.php';

class BazelJSONTestCase extends KWWebTestCase
{
    public function __construct()
    {
        parent::__construct();
        $this->PDO = get_link_identifier()->getPdo();
        $this->BuildId = 0;
    }

    public function __destruct()
    {
        if ($this->BuildId > 0) {
            remove_build($this->BuildId);
        }
    }

    public function testBazelJSON()
    {
        // Do the POST step of our submission.
        $fields = [
            'project' => 'InsightExample',
            'build' => 'bazel_json',
            'site' => 'localhost',
            'stamp' => '20170823-1835-Experimental',
            'starttime' => '1503513355',
            'endtime' => '1503513355',
            'track' => 'Experimental',
            'type' => 'BazelJSON',
            'datafilesmd5[0]=' => '0a9b0aeeb73618cd10d6e1bee221fd71'];
        $client = new GuzzleHttp\Client();
        global $CDASH_BASE_URL;
        try {
            $response = $client->request(
                'POST',
                $CDASH_BASE_URL . '/submit.php',
                [
                    'form_params' => $fields
                ]
            );
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $this->fail('POST submit failed: ' . $e->getMessage());
            return false;
        }

        // Parse buildid for subsequent PUT request.
        $response_array = json_decode($response->getBody(), true);
        $this->BuildId = $response_array['buildid'];

        // Do the PUT request.
        $puturl = $this->url . "/submit.php?type=BazelJSON&md5=0a9b0aeeb73618cd10d6e1bee221fd71&filename=bazel_BEP.json&buildid=$this->BuildId";
        $filename = dirname(__FILE__) . '/data/bazel_BEP.json';
        if ($this->uploadfile($puturl, $filename) === false) {
            $this->fail("Upload failed for bazel_BEP.json");
            return false;
        }

        // Validate the build.
        $stmt = $this->PDO->query(
                "SELECT builderrors, buildwarnings, testfailed, testpassed
                FROM build WHERE id = $this->BuildId");
        $row = $stmt->fetch();

        $answer_key = [
            'builderrors' => 1,
            'buildwarnings' => 2,
            'testfailed' => 1,
            'testpassed' => 1
        ];
        foreach ($answer_key as $key => $expected) {
            $found = $row[$key];
            if ($found != $expected) {
                $this->fail("Expected $expected for $key but found $found");
            }
        }
    }
}
