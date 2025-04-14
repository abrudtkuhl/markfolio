<?php

/*
|--------------------------------------------------------------------------
| Pest Configuration File
|--------------------------------------------------------------------------
|
| Markfolio uses Pest for testing, which provides a beautiful experience for
| building and maintaining tests for your Laravel packages. This file
| configures Pest for testing Markfolio packages specifically.
|
*/

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
|
| Here we define custom expectations for testing Markdown functionality
|
*/

expect()->extend('toBeMarkdownPage', function (?string $title = null) {
    expect($this->value)
        ->toBeArray()
        ->toHaveKey('content')
        ->toHaveKey('meta');

    if ($title) {
        expect($this->value['title'])->toBe($title);
    } else {
        expect($this->value)->toHaveKey('title');
    }

    return $this;
});

expect()->extend('toBeValidHtml', function () {
    expect($this->value)
        ->toBeString()
        ->toContain('>')
        ->not->toContain('```');

    return $this;
});

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
|
| These functions help create test fixtures and mock data
|
*/

use Illuminate\Support\Facades\File;

function createMarkdownFile(string $path, ?string $title = null, ?string $content = null): void
{
    $filePath = resource_path('content/'.ltrim($path, '/'));
    $dirName = dirname($filePath);

    if (! is_dir($dirName)) {
        mkdir($dirName, 0755, true);
    }

    $title = $title ?? basename($path, '.md');
    $content = $content ?? "# {$title}";

    $fileContent = <<<EOT
---
title: {$title}
layout: layouts.app
---

{$content}
EOT;

    file_put_contents($filePath, $fileContent);
}

function createTestMarkdownFile(string $path, ?string $title = null, ?string $layout = null): void
{
    $title = $title ?? basename($path);
    $layout = $layout ?? 'layouts.app';

    $content = <<<EOT
---
title: {$title}
layout: {$layout}
---

# {$title}

This is a test page.
EOT;

    File::put(resource_path("content/{$path}.md"), $content);
}
