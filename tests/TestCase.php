<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Markfolio\MarkfolioServiceProvider;
use Markfolio\Middleware\MarkdownRenderer;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [MarkfolioServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('markfolio.content_directory', resource_path('content'));
        $app['config']->set('markfolio.default_layout', 'layouts.app');
    }

    protected function defineRoutes($router)
    {
        $router->get('/{path}', function ($path) {
            $middleware = new MarkdownRenderer();
            return $middleware->handle(request(), function () {
                return response('Not found', 404);
            });
        })->where('path', '.*')->middleware(MarkdownRenderer::class);
    }
    
    protected function defineDatabaseMigrations(): void
    {
        // Define any migrations needed for testing
    }
} 