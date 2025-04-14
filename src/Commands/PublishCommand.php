<?php

namespace Markfolio\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishCommand extends Command
{
    protected $signature = 'markfolio:publish 
                            {--force : Overwrite any existing files}
                            {--config : Publish the config file}
                            {--views : Publish the view files}
                            {--all : Publish all assets}';

    protected $description = 'Publish Markfolio assets (config, views)';

    public function handle(): int
    {
        $this->info('Publishing Markfolio assets...');

        // Determine what to publish
        $publishAll = $this->option('all') || (! $this->option('config') && ! $this->option('views'));
        $publishConfig = $this->option('config') || $publishAll;
        $publishViews = $this->option('views') || $publishAll;

        // Force flag
        $force = (bool) $this->option('force');

        // Publish config
        if ($publishConfig) {
            $this->publishConfig($force);
        }

        // Publish views
        if ($publishViews) {
            $this->publishViews($force);
        }

        // Ensure content directory exists
        $this->ensureContentDirectoryExists();

        $this->info('Markfolio assets published successfully!');

        return self::SUCCESS;
    }

    protected function publishConfig(bool $force): void
    {
        $configPath = config_path('markfolio.php');
        $packageConfigPath = __DIR__.'/../../config/markfolio.php';

        if (File::exists($configPath) && ! $force) {
            if (! $this->confirm("The file {$configPath} already exists. Do you want to overwrite it?")) {
                return;
            }
        }

        File::copy($packageConfigPath, $configPath);
        $this->info('Configuration file published to: '.$configPath);
    }

    protected function publishViews(bool $force): void
    {
        $viewsPath = resource_path('views/vendor/markfolio');
        $packageViewsPath = __DIR__.'/../../resources/views';

        if (File::exists($viewsPath) && ! $force) {
            if (! $this->confirm("The views directory {$viewsPath} already exists. Do you want to overwrite it?")) {
                return;
            }
        }

        if (! File::isDirectory($viewsPath)) {
            File::makeDirectory($viewsPath, 0755, true);
        }

        File::copyDirectory($packageViewsPath, $viewsPath);
        $this->info('View files published to: '.$viewsPath);
    }

    protected function ensureContentDirectoryExists(): void
    {
        $contentDir = config('markfolio.content_directory', resource_path('content'));

        if (! File::isDirectory($contentDir)) {
            File::makeDirectory($contentDir, 0755, true);
            $this->info('Created content directory: '.$contentDir);
        } else {
            $this->info('Content directory already exists: '.$contentDir);
        }
    }
}
