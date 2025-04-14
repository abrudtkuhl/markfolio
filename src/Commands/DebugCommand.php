<?php

namespace Markfolio\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Markfolio\PageParser;
use Symfony\Component\Yaml\Yaml;

class DebugCommand extends Command
{
    protected $signature = 'markfolio:debug {path?}';

    protected $description = 'Debug markdown page rendering';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! $path) {
            $path = $this->ask('Enter the path to the markdown file');
        }

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $parser = new PageParser($path);
        $viewData = $parser->toViewData();

        $this->info('Front Matter:');
        $this->line(Yaml::dump($viewData['meta'] ?? [], 4, 2));

        $this->info("\nContent:");
        $this->line($viewData['content'] ?? '');

        $this->info("\nRendered HTML:");
        $this->line($viewData['content'] ?? '');

        $this->info("\nFolio Integration:");
        if (class_exists('Laravel\\Folio\\Folio')) {
            $this->line('Folio is installed and available');
        } else {
            $this->line('Folio is not installed. Install it with: composer require laravel/folio');
        }

        return self::SUCCESS;
    }
}
