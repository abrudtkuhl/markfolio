<?php

namespace Markfolio\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Markfolio\PageParser;
use Symfony\Component\HttpFoundation\Response;

class MarkdownRenderer
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get the content directory from config
        $contentDir = config('markfolio.content_directory', resource_path('content'));

        // Process the path
        $path = $request->path();
        if (empty($path)) {
            $path = '/';
        }

        // Debug path information - always log this regardless of debug setting
        Log::info("Markfolio Debug: Request path: '{$path}'");
        Log::info("Markfolio Debug: Content directory: '{$contentDir}'");

        // Check if it's an index path
        $mdFile = rtrim($contentDir, '/').'/'.$path.'.md';
        $indexFile = rtrim($contentDir, '/').'/'.$path.'/index.md';

        // Debug file paths
        Log::info("Markfolio Debug: Looking for file: '{$mdFile}'");
        Log::info("Markfolio Debug: Looking for index: '{$indexFile}'");
        Log::info('Markfolio Debug: File exists check: '.(file_exists($mdFile) ? 'true' : 'false'));

        // Find the correct file to use
        $filePath = null;
        if (file_exists($mdFile)) {
            $filePath = $mdFile;
            Log::info("Markfolio Debug: Found markdown file: '{$mdFile}'");
        } elseif (file_exists($indexFile)) {
            $filePath = $indexFile;
            Log::info("Markfolio Debug: Found index markdown file: '{$indexFile}'");
        } else {
            Log::info("Markfolio Debug: No markdown file found for path: '{$path}'");
        }

        // If no file is found, continue normal request
        if (! $filePath) {
            return $next($request);
        }

        // If we found a file, regardless of how we got here, render it
        // Check if caching is enabled
        $cacheEnabled = config('markfolio.cache.enabled', true);
        $cacheTtl = config('markfolio.cache.ttl', 3600);

        if ($cacheEnabled) {
            $cacheKey = 'markfolio_'.md5($filePath);

            return Cache::remember($cacheKey, $cacheTtl, function () use ($filePath) {
                return $this->renderMarkdownPage($filePath);
            });
        }

        return $this->renderMarkdownPage($filePath);
    }

    /**
     * Render a markdown page
     *
     * @param  string  $filePath  Path to the markdown file
     * @return \Illuminate\Http\Response
     */
    public function renderMarkdownPage($filePath)
    {
        $parser = new PageParser($filePath);
        $data = $parser->toViewData();

        $layout = $data['meta']['layout'] ?? config('markfolio.default_layout');
        $data['layout'] = $layout;

        // Debug information if enabled
        if (config('markfolio.debug', false)) {
            Log::info("Markfolio: Rendering page: {$filePath}");
            Log::info('Markfolio: Using layout: '.($layout ?: 'none (direct rendering)'));
        }

        if ($layout === null) {
            if (config('markfolio.debug', false)) {
                Log::info('Markfolio: No layout specified, using markdown-direct view');
            }

            try {
                return response(view('markfolio::markdown-direct', $data));
            } catch (\Exception $e) {
                Log::error('Markfolio: Error rendering markdown-direct view: '.$e->getMessage());

                return response('Error rendering markdown-direct view: '.$e->getMessage(), 500);
            }
        }

        // Verify layout exists
        $layoutPath = str_replace('.', '/', $layout).'.blade.php';
        $layoutExists = file_exists(resource_path('views/'.$layoutPath));

        if (! $layoutExists) {
            if (config('markfolio.debug', false)) {
                Log::warning("Markfolio: Layout does not exist: {$layout}, fallback to direct rendering");

                return response("❌ Default layout does not exist: {$layout}", 500);
            }

            // Fallback to direct rendering when layout doesn't exist
            try {
                return response(view('markfolio::markdown-direct', $data));
            } catch (\Exception $e) {
                Log::error('Markfolio: Error rendering markdown-direct view: '.$e->getMessage());

                return response('Error rendering markdown-direct view: '.$e->getMessage(), 500);
            }
        }

        try {
            return response(view('markfolio::markdown', $data));
        } catch (\Exception $e) {
            Log::error('Markfolio: Error rendering markdown view: '.$e->getMessage());

            return response('Error rendering markdown view: '.$e->getMessage(), 500);
        }
    }
}
