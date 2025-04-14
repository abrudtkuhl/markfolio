<?php

namespace Markfolio\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Folio\Folio;

class DebugCommand extends Command
{
    protected $signature = 'markfolio:debug';
    protected $description = 'Debug Markfolio setup';

    public function handle()
    {
        $this->info('Markfolio Debug Information');
        $this->info('===========================');
        
        $this->checkFolioInstallation();
        $this->checkContentDirectory();
        $this->checkLayouts();
        $this->checkViews();
        
        return 0;
    }
    
    protected function checkFolioInstallation()
    {
        $this->info('Checking Folio installation:');
        
        if (!class_exists(Folio::class)) {
            $this->error('❌ Laravel Folio is not installed. Install it with: composer require laravel/folio');
            return;
        }
        
        $this->info('✅ Laravel Folio is installed');
        $this->info('Folio version: ' . $this->getFolioVersion());
        
        $this->info('Folio API check:');
        if (method_exists(Folio::class, 'route')) {
            $this->info('✅ Using modern Folio API (route)');
        } elseif (method_exists(Folio::class, 'path')) {
            $this->info('✅ Using legacy Folio API (path)');
        } else {
            $this->error('❌ Unknown Folio API version');
        }
    }
    
    protected function getFolioVersion()
    {
        $composerLock = base_path('composer.lock');
        if (File::exists($composerLock)) {
            $lockData = json_decode(File::get($composerLock), true);
            foreach ($lockData['packages'] ?? [] as $package) {
                if ($package['name'] === 'laravel/folio') {
                    return $package['version'];
                }
            }
        }
        return 'unknown';
    }
    
    protected function checkContentDirectory()
    {
        $contentDir = config('markfolio.content_directory', resource_path('content'));
        $this->info('Checking content directory:');
        $this->info("Content directory: {$contentDir}");
        
        if (!is_dir($contentDir)) {
            $this->error("❌ Content directory does not exist: {$contentDir}");
            $this->info("Creating content directory...");
            mkdir($contentDir, 0755, true);
            $this->info("✅ Content directory created");
            return;
        }
        
        $this->info("✅ Content directory exists");
        
        $markdownFiles = $this->findMarkdownFiles($contentDir);
        $count = count($markdownFiles);
        
        $this->info("Found {$count} markdown files:");
        
        foreach ($markdownFiles as $file) {
            $relativePath = str_replace($contentDir . '/', '', $file);
            $url = $this->fileToUrl($relativePath);
            $this->info("- {$relativePath} → {$url}");
        }

        // Specific check for hello-world.md
        $helloWorldPath = $contentDir . '/hello-world.md';
        if (file_exists($helloWorldPath)) {
            $this->info('');
            $this->info('🔍 Testing hello-world.md specifically:');
            $this->info("✅ hello-world.md exists at: {$helloWorldPath}");
            $this->info("Expected URL: /hello-world");
            
            // Check file permissions
            $perms = fileperms($helloWorldPath);
            $this->info("File permissions: " . substr(sprintf('%o', $perms), -4));
            
            // Check content
            $content = file_get_contents($helloWorldPath);
            $this->info("File size: " . strlen($content) . " bytes");
            $this->info("First 100 chars: " . substr($content, 0, 100));
            
            // Show how Markfolio would process this
            $this->info("Simulating middleware processing:");
            $request = request()->create('/hello-world');
            $this->info("Request path: " . $request->path());
            
            $mdFile = rtrim($contentDir, '/') . '/' . $request->path() . '.md';
            $this->info("Would look for file: {$mdFile}");
            $this->info("File exists check: " . (file_exists($mdFile) ? '✅ Yes' : '❌ No'));
        }
    }
    
    protected function fileToUrl($filePath)
    {
        // Remove .md extension and convert to URL
        $url = str_replace('.md', '', $filePath);
        
        // Handle index files
        if (basename($url) === 'index') {
            $url = dirname($url);
            if ($url === '.') {
                return '/';
            }
        }
        
        return '/' . $url;
    }
    
    protected function findMarkdownFiles($dir)
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    protected function checkLayouts()
    {
        $this->info('Checking layouts:');
        
        $defaultLayout = config('markfolio.default_layout');
        $this->info("Default layout: " . ($defaultLayout ?: 'none (disabled)'));
        
        if ($defaultLayout) {
            $layoutPath = resource_path('views/' . str_replace('.', '/', $defaultLayout) . '.blade.php');
            
            if (File::exists($layoutPath)) {
                $this->info("✅ Default layout exists: {$layoutPath}");
            } else {
                $this->error("❌ Default layout does not exist: {$layoutPath}");
            }
        }
    }
    
    protected function checkViews()
    {
        $this->info('Checking package views:');
        
        $viewPaths = config('view.paths');
        $packageViews = [];
        
        foreach ($viewPaths as $path) {
            $vendorPath = $path . '/vendor/markfolio';
            if (is_dir($vendorPath)) {
                $packageViews[] = $vendorPath;
            }
        }
        
        if (empty($packageViews)) {
            $this->info('📌 Markfolio views not published to application');
            $this->info('   Using package views from: ' . __DIR__ . '/../../resources/views');
        } else {
            $this->info('✅ Markfolio views published to:');
            foreach ($packageViews as $path) {
                $this->info("  - {$path}");
            }
        }
        
        $requiredViews = ['markdown.blade.php', 'markdown-direct.blade.php'];
        $packageViewsPath = __DIR__ . '/../../resources/views';
        
        $this->info('Required views in package:');
        foreach ($requiredViews as $view) {
            $exists = file_exists($packageViewsPath . '/' . $view);
            $this->info(($exists ? '✅' : '❌') . " {$view}");
            
            if (!$exists) {
                $this->error("   Missing required view: {$view}");
                $this->info("   This could cause 404 errors when rendering markdown pages.");
            }
        }
        
        // Check if views can be resolved
        $this->info('Testing view resolution:');
        try {
            $resolved = view()->exists('markfolio::markdown');
            $this->info(($resolved ? '✅' : '❌') . ' markfolio::markdown view can be resolved');
            
            if (!$resolved) {
                $this->error("   Cannot resolve markfolio::markdown view");
                $this->info("   This will cause 404 errors when rendering markdown pages.");
            }
        } catch (\Exception $e) {
            $this->error('❌ Error testing view resolution: ' . $e->getMessage());
        }
        
        try {
            $resolved = view()->exists('markfolio::markdown-direct');
            $this->info(($resolved ? '✅' : '❌') . ' markfolio::markdown-direct view can be resolved');
            
            if (!$resolved) {
                $this->error("   Cannot resolve markfolio::markdown-direct view");
                $this->info("   This will cause 404 errors when rendering markdown pages without layouts.");
            }
        } catch (\Exception $e) {
            $this->error('❌ Error testing view resolution: ' . $e->getMessage());
        }
    }
} 