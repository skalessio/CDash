<?php

namespace App\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CdashController extends Controller
{
    public function index(Request $request)
    {
      $path = $request->path();
      $path = $path === '/' ? "/index.php" : $path;

      /** @var FilesystemAdapter $cdashfs */
      $cdashfs = Storage::disk('cdash');

      if ($cdashfs->exists($path)) {
          $file = $cdashfs->getDriver()
              ->getAdapter()
              ->applyPathPrefix($path);
          chdir(app_path('CDash'));
          require $file;
      } else {
        $response = new Response('<strong>Not found</strong>', 404);
        return $response->header('Content-Type', 'text/html');
      }
    }
}
