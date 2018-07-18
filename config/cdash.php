<?php
$cdash_dir = __DIR__ .
  DIRECTORY_SEPARATOR .
  '..' .
  DIRECTORY_SEPARATOR .
  'app' .
  DIRECTORY_SEPARATOR .
  'CDash';

include $cdash_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
include $cdash_dir . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'version.php';

$config = get_defined_vars();
return [
  /*
    |--------------------------------------------------------------------------
    | Original Configuration Settings
    |--------------------------------------------------------------------------
    |
    | This provides access to the original CDash configuration file
    |
    */
  'deprecated' => $config,

  /*
    |--------------------------------------------------------------------------
    | Administration Configuration Settings
    |--------------------------------------------------------------------------
    |
    | This value determines the CDash project settings
    |
    */
  'admin' => [
    'email' => $config['CDASH_EMAILADMIN'] ?: '',
  ],

  /*
    |--------------------------------------------------------------------------
    | Project Configuration Settings
    |--------------------------------------------------------------------------
    |
    | This value determines the CDash project settings
    |
    */
  'projects' => [
    'max_builds' => $config['CDASH_BUILDS_PER_PROJECT'] ?: 0,
    'with_unlimited_builds' => $config['CDASH_UNLIMITED_PROJECTS'] ?: [],
  ],

  /*
  |--------------------------------------------------------------------------
  | Submission Configuration Settings
  |--------------------------------------------------------------------------
  |
  | This value determins the CDash submission settings
  |
  */
  'submissions' => [
    'content_type' => 'xml', // allowed json or xml, determines which blade template to use
  ],

  /*
   |--------------------------------------------------------------------------
   | Version information
   |--------------------------------------------------------------------------
   |
   | This provides the application with access to the in-use version of CDash
   |
   */
  'version' => [
    'major' => $config['CDASH_VERSION_MAJOR'],
    'minor' => $config['CDASH_VERSION_MINOR'],
    'patch' => $config['CDASH_VERSION_PATCH'],
    'string' => $config['CDASH_VERSION'],
  ],

];
