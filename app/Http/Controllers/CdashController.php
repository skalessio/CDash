<?php

namespace App\Http\Controllers;

use App\Project;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Suin\RSSWriter\SimpleXMLElement;

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
        chdir(app_path('CDash/public'));


        ob_start();
        $nocontent = require $file;
        $content = ob_get_contents();
        ob_end_clean();

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $type = "text/html";

        switch ($extension)
        {
          case 'jpg':
          case 'png':
          case 'gif':
          case 'jpeg':
            $type = "image/{$extension}";
            break;
          case 'css':
            $type = "text/css";
            break;
          case 'js':
            $type = "application/javascript";
            break;
          default:
            if (json_decode($content)) {
              $type = "application/json";
            }
          }

          if (is_array($nocontent) && isset($nocontent['Location'])) {
            return redirect($nocontent['Location']);
          }

          $response = new Response($content);
          return $response->header('Content-Type', $type);

      } else {
        $response = new Response('<strong>Not found</strong>', 404);
        return $response->header('Content-Type', 'text/html');
      }
    }

    public function submit(Request $request, Response $response)
    {
      // initialize action's variables
      $md5 = null;
      $message = '';
      $status = 'ERROR';
      $type = "application/{$this->expectsType()}";
      $project_name = $request->query('project');
      $email = Config::get('cdash.admin.email');

      $response->header('Content-Type', $type);

      try {
        /** @var Project $project */
        $project = Project::where('name', $project_name)->first();

        // Project not found
        if (!$project) {
          $message = 'Not a valid project';
          $content = $this->createView($status, $message);
          return $response->setStatusCode(404)
            ->setContent($content);
        }

        // Project at build capacity
        if ($project->isAtBuildCapacity()) {
          $message = "Maximum number of builds reached for {$project->name}. Contact {$email} for support.";
          $content = $this->createView($status, $message);
          return $response->setStatusCode(409)->setContent($content);
        }

        $submission = $request->getContent(true);

        /*
         * This should look something like:
         *   $factory = $this->>getXmlHandlerFactory();
         *   $handler = $factory->create();
         *   if ($handler->parse($submission)) {
         *     $message = '';
         *     ...
         *    }
         *
         * Everything else in between should either be handled in middleware, in
         * a different action (controller), or, possibly, not at all.
         */

        // TODO: refactor out inclusion of this old code ASAP
        // prevent old code from squawking
        ob_start();
        require_once app_path('CDash/include/do_submit.php');
        do_submit($submission, $project->id);
        // yikes
        $output = ob_get_contents();
        ob_end_clean();

        $xml = new SimpleXMLElement($output);
        if ($xml) {
          $message = isset($xml->message) ? $xml->message : '';
          $status = isset($xml->status) ? $xml->status : $status;
          $md5 = isset($xml->md5) ? $xml->md5 : null;
        } else {
          $message = 'Unexpected output';
          $response->setStatusCode(500);
        }
      } catch (\Exception $exception) {
        $status = 'ERROR';
        $message = $exception->getMessage();
        $response->setStatusCode(500);
      }

      $content = $this->createView($status, $message, $md5);

      return $response->setContent($content);
    }

    protected function createView($status, $message, $md5 = null, $version = null)
    {
      $version = $version ?: Config::get('cdash.version.string');
      $type = $this->expectsType();

      return View::make("cdash.{$type}")
        ->with('version', $version)
        ->with('status', $status)
        ->with('message', $message)
        ->with('md5', $md5);
    }

    protected function expectsType()
    {
      return Config::get('cdash.submissions.content_type');
    }
}
