<?php

namespace Markfolio\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Markfolio\PageParser;
use Symfony\Component\Yaml\Yaml;

class DebugCommand extends Command
{
    protected $signature = 'markfolio:debug {path?}';

    protected $description = 'Debug markdown page rendering';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!$path) {
            $path = $this->ask('Enter the path to the markdown file');
        }

        if (!File::exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $content = File::get($path);
        $parser = new PageParser();
        $page = $parser->parse($content);

        $this->info('Front Matter:');
        $this->line(Yaml::dump($page->frontMatter(), 4, 2));

        $this->info("\nContent:');
        $this->line($page->content());

        $this->info("\nRendered HTML:');
        $this->line($page->toHtml());

        if (class_exists('Laravel\Folio\Folio')) {
            $this->info("\nFolio Integration:');
            $this->line('Folio is installed and available');
        } else {
            $this->info("\nFolio Integration:');
            $this->line('Folio is not installed. Install it with: composer require laravel/folio');
        }

        return self::SUCCESS;
    }
} 