<?php
namespace CDash\Test\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

trait CTestXMLSubmissions
{
  protected $stubs = [];
  protected $projectName;
  protected $dataDir;

  protected function assertCTestSubmissionOK($key, $method = 'PUT', $endpoint = '/submit')
  {
    $file = "{$key}.xml";

    if (isset($this->stubs[$file])) {
      $content = $this->getRawFile($file);
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
    } else {
      $this->assertTrue(false, "File, {$file}, not prepared for submission");
    }

    return $this;
  }

  protected function getFileForUpload($key)
  {
    if (isset($this->stubs[$key])) {
      $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $key;

      copy($this->stubs[$key], $path);
      $size = filesize($path);
      $type = 'text/xml';

      return new UploadedFile($path, $key, $type, $size, null, true);
    }
  }

  protected function getRawFile($key)
  {
    if (isset($this->stubs[$key])) {
      return file_get_contents($this->stubs[$key]);
    }
  }

  protected function getMd5HashForFile($key)
  {
    if (isset($this->stubs[$key])) {
      return md5_file($this->stubs[$key]);
    }
  }
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
