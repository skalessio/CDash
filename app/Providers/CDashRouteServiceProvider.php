<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CDashRouteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Request $request, Router $router)
    {
        $routes = $router->getRoutes();
        try {
          $routes->match($request);
        } catch (NotFoundHttpException $exception) {
          $path = $request->path();

          $method = strtolower($request->getMethod());
          $this->app->call([$router, $method], [
            'uri' => "/$path",
            'action' => 'App\Http\Controllers\CdashController@index'
          ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
