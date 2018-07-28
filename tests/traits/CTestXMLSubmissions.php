<?php
/**
 * =========================================================================
 *   Program:   CDash - Cross-Platform Dashboard System
 *   Module:    $Id$
 *   Language:  PHP
 *   Date:      $Date$
 *   Version:   $Revision$
 *
 *   Copyright (c) Kitware, Inc. All rights reserved.
 *   See LICENSE or http://www.cdash.org/licensing/ for details.
 *
 *   This software is distributed WITHOUT ANY WARRANTY; without even
 *   the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 *   PURPOSE. See the above copyright notices for more information.
 * =========================================================================
 */

namespace CDash\Test\Traits;

use Illuminate\Support\Facades\File;

trait CTestXMLSubmissions
{
  /** @var array $stubs */
  protected $stubs = [];

  /** @var string $projectName */
  protected $projectName;

  /**
   * Submits a ctest xml file located in a previously intialized directory to an endpoint for
   * processing
   *
   * @param $key
   * @param string $method
   * @param string $endpoint
   * @return $this
   */
  protected function assertCTestSubmissionOK($key, $method = 'PUT', $endpoint = '/submit')
  {
    $content = $this->prepareSubmissionFile($key);
    $uri = "/{$endpoint}?project={$this->projectName}";
    $server = [
      'HTTP_CONTENT_LENGTH' => mb_strlen($content, '8bit'),
      'CONTENT_TYPE' => 'application/xml',
      'HTTP_ACCEPT' => 'application/xml',
    ];
    $this->call($method, $uri, [], [], [], $server, $content);
    $actual = $this->response->getStatusCode();
    $this->assertTrue(
      $this->response->isOk(),
      "Expected status code 200, got {$actual} trying to submit {$key}."
    );
    $this->validateXmlStatus();
    return $this;
  }

  /**
   * Prepares a file that has been initialized for submission to endpoint.
   *
   * @param $file
   * @return bool|string
   */
  protected function prepareSubmissionFile($file)
  {
    $key = "{$file}.xml";
    if (isset($this->stubs[$key])) {
      $content = $this->getRawFile($key);
      return $content;
    } else {
      $this->fail("File, `$key', was not initialized.");
    }
  }

  /**
   * A successful submission will have a status equal to 'OK'
   */
  protected function validateXmlStatus()
  {
    $dom = $this->getResponseDOM();

    /** @var \DOMElement $node */
    foreach ($dom->childNodes as $node) {
      if ($node->getNodePath() === '/cdash/status') {
        $msg = "Expected OK, received {$node->nodeValue} from submission endpoint";
        $this->assertEquals('OK', $node->nodeValue, $msg);
      }
    }
  }

  /**
   * Converts the response xml from the submission endpoint into a DOMDocument
   *
   * @return \DOMDocument
   */
  protected function getResponseDOM()
  {
    $dom = new \DOMDocument();
    try {
      $dom->loadXML($this->response->getContent());
    } catch (\Exception $e) {
      $this->fail("Unable to parse response XML: {$e->getMessage()}");
    }
    return $dom;
  }

  /**
   * Returns the contents of file in a directory that has been previously initialized
   *
   * @param $key
   * @return bool|string
   */
  protected function getRawFile($key)
  {
    if (isset($this->stubs[$key])) {
      return file_get_contents($this->stubs[$key]);
    }
  }

  /**
   * Initializes a project directory with all of the ctest xml files
   *
   * @param $directory
   * @param null $project
   * @param string $path
   */
  protected function initProjectDirectory($directory, $project = null, $path = 'CDash.tests.data')
  {
    $this->projectName = $project ?: $directory;
    $path = implode(DIRECTORY_SEPARATOR, explode('.', $path));
    $directory = app_path($path . DIRECTORY_SEPARATOR . $directory);
    if (is_readable($directory)) {
      $glob = $directory . DIRECTORY_SEPARATOR . "*.xml";
      $files = File::glob($glob);
      foreach ($files as $file) {
        $pos = strrpos($file, DIRECTORY_SEPARATOR) + 1;
        $key = substr($file, $pos);
        $this->stubs[$key] = $file;
      }
    } else {
      $this->fail("Project directory {$directory} is not readable");
    }
  }
}
