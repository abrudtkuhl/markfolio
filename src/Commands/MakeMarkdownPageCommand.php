<?php

namespace Markfolio\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeMarkdownPageCommand extends Command
{
    protected $signature = 'make:markdown-page {path} {--title=} {--layout=}';

    protected $description = 'Create a new markdown page compatible with Folio';

    public function handle(): int
    {
        $path = (string)$this->argument('path');
        $title = $this->option('title') ? (string)$this->option('title') : Str::title(basename($path));
        $layout = $this->option('layout') ? (string)$this->option('layout') : config('markfolio.default_layout', 'layouts.app');
        
        $contentDir = config('markfolio.content_directory', resource_path('content'));
        $fullPath = $contentDir . '/' . ltrim((string)$path, '/') . '.md';
        
        if (File::exists($fullPath)) {
            if (!$this->confirm("The file {$fullPath} already exists. Do you want to overwrite it?")) {
                return self::FAILURE;
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
        $this->info("This page will be accessible at: /" . ltrim((string)$path, '/'));
        
        return self::SUCCESS;
    }
    
    protected function getTimestamp(): string
    {
        return now()->toIso8601String();
    }
} 