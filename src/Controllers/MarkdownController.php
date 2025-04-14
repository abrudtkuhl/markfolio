<?php

namespace Markfolio\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Markfolio\Middleware\MarkdownRenderer;

class MarkdownController
{
    /**
     * Handle markdown page requests
     */
    public function __invoke(Request $request, string $path = '')
    {
        // Get the content directory
        $contentDir = config('markfolio.content_directory', resource_path('content'));
        
        // Use the path from the request if it wasn't captured in the route
        if (empty($path)) {
            $path = $request->path();
        }
        
        // Skip any routes that clearly aren't for markdown files
        if (str_contains($path, '.') || $path === 'favicon.ico' || str_starts_with($path, '_')) {
            return response('Not Found', 404);
        }
        
        Log::info("Markfolio Controller: Checking path: {$path}");
        
        // Look for markdown file
        $mdFile = rtrim($contentDir, '/') . '/' . $path . '.md';
        $indexFile = rtrim($contentDir, '/') . '/' . $path . '/index.md';
        
        Log::info("Markfolio Controller: Looking for: {$mdFile}");
        
        if (file_exists($mdFile)) {
            // Use the renderer directly
            $renderer = new MarkdownRenderer();
            return $renderer->renderMarkdownPage($mdFile);
        } elseif (file_exists($indexFile)) {
            // Use the renderer directly
            $renderer = new MarkdownRenderer();
            return $renderer->renderMarkdownPage($indexFile);
        }
        
        // No markdown file found
        return response('Not Found', 404);
    }
} 