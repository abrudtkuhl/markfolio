<?php

namespace Markfolio\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeMarkdownPageCommand extends Command
{
    protected $signature = 'make:markdown-page {path} {--title=} {--layout=}';

    protected $description = 'Create a new markdown page compatible with Folio';

    public function handle()
    {
        $path = $this->argument('path');
        $title = $this->option('title') ?? Str::title(basename($path));
        $layout = $this->option('layout') ?? config('markfolio.default_layout', 'layouts.app');
        
        $contentDir = config('markfolio.content_directory', resource_path('content'));
        $fullPath = $contentDir . '/' . ltrim($path, '/') . '.md';
        
        if (File::exists($fullPath)) {
            if (!$this->confirm("The file {$fullPath} already exists. Do you want to overwrite it?")) {
                return 1;
            }
        }
        
        $dirName = dirname($fullPath);
        if (!File::isDirectory($dirName)) {
            File::makeDirectory($dirName, 0755, true);
        }
        
        $content = <<<EOT
---
title: {$title}
layout: {$layout}
created_at: {$this->getTimestamp()}
---

# {$title}

Write your markdown content here.

EOT;
        
        File::put($fullPath, $content);
        
        $this->info("Markdown page created successfully: {$fullPath}");
        $this->info("This page will be accessible at: /" . ltrim($path, '/'));
        
        return 0;
    }
    
    protected function getTimestamp()
    {
        return now()->toIso8601String();
    }
} 