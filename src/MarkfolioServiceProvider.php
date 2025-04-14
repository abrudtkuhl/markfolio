<?php

namespace Markfolio;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;
use Markfolio\Commands\DebugCommand;
use Markfolio\Commands\MakeMarkdownPageCommand;
use Markfolio\Commands\PublishCommand;
use Markfolio\Controllers\MarkdownController;
use Markfolio\Import\ContentImporter;
use Markfolio\Middleware\MarkdownRenderer;

class MarkfolioServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/markfolio.php', 'markfolio'
        );

        // Register ContentManager as a singleton
        $this->app->singleton(ContentManager::class, function ($app) {
            return new ContentManager(config('markfolio.content_directory'));
        });

        // Register ContentImporter
        $this->app->bind(ContentImporter::class, function ($app) {
            return new ContentImporter(config('markfolio.content_directory'));
        });

        // Register useful facade aliases
        $this->app->alias(ContentManager::class, 'markfolio');
        $this->app->alias(ContentImporter::class, 'markfolio.importer');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeMarkdownPageCommand::class,
                PublishCommand::class,
                DebugCommand::class,
            ]);
        }

        // Load package views - make sure this is before publishing
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'markfolio');

        // This publishes views to the user's application, but is optional
        $this->publishes([
            __DIR__.'/../config/markfolio.php' => config_path('markfolio.php'),
            __DIR__.'/../resources/views' => resource_path('views/vendor/markfolio'),
        ], 'markfolio');

        // Skip Folio integration if we're running tests
        if ($this->app->environment('testing')) {
            return;
        }

        // Only run Folio integration if the package is present
        if (class_exists(Folio::class)) {
            // Get the content directory from config - use resource_path by default
            $contentDir = config('markfolio.content_directory', resource_path('content'));

            // Ensure the directory exists
            if (! is_dir($contentDir)) {
                mkdir($contentDir, 0755, true);
            }

            // Log the content directory being used
            Log::info("Markfolio: Registering content directory: {$contentDir}");

            // Register the MarkdownRenderer middleware with the router
            $this->app->make('router')->aliasMiddleware('markdown', MarkdownRenderer::class);

            // Register with Folio
            if (method_exists(Folio::class, 'route')) {
                // New Folio API (v1.x)
                Log::info('Markfolio: Using Folio::route() method');

                // Make sure path is absolute
                $absoluteContentDir = realpath($contentDir) ?: $contentDir;
                Log::info("Markfolio: Using absolute content path: {$absoluteContentDir}");

                // The 'web' middleware is required for sessions
                Folio::route($absoluteContentDir, middleware: [
                    '*' => ['web', 'markdown'],
                ]);

            } elseif (method_exists(Folio::class, 'path')) {
                // Legacy Folio API (v0.x)
                Log::info('Markfolio: Using Folio::path() method');

                // Make sure path is absolute
                $absoluteContentDir = realpath($contentDir) ?: $contentDir;
                Log::info("Markfolio: Using absolute content path: {$absoluteContentDir}");

                $folioInstance = app(Folio::class);
                $folioInstance->path($absoluteContentDir);

                // Register middleware in both ways for compatibility
                $folioInstance->middleware([
                    '*' => ['web', 'markdown'],
                ]);

                if (method_exists($folioInstance, 'middleware')) {
                    $folioInstance->middleware([
                        'markdown' => MarkdownRenderer::class,
                    ]);
                }
            }

            // Simple debug info
            if (config('app.debug', false)) {
                $this->logMarkdownFiles($contentDir);
            }

            // Register a fallback route to handle cases where Folio isn't catching markdown files
            $this->registerFallbackRoute($contentDir);

        } else {
            Log::warning('Markfolio: Laravel Folio not found. Markdown pages will not be routed.');
        }
    }

    protected function registerFallbackRoute($contentDir)
    {
        $router = $this->app->make('router');

        // Use a controller class instead of a closure to avoid serialization issues
        $router->get('{path?}', MarkdownController::class)
            ->where('path', '.*')
            ->middleware(['web'])
            ->name('markfolio.fallback');

        Log::info('Markfolio: Registered fallback route for markdown files');
    }

    protected function logMarkdownFiles($contentDir)
    {
        if (! is_dir($contentDir)) {
            return;
        }

        $files = array_filter(
            scandir($contentDir) ?: [],
            fn ($file) => pathinfo($file, PATHINFO_EXTENSION) === 'md'
        );

        Log::info('Markfolio: Found '.count($files)." markdown files in {$contentDir}");

        foreach ($files as $file) {
            Log::info("Markfolio: Registered markdown file: {$file}");
        }
    }
}
