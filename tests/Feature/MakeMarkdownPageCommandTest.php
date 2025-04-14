<?php

use Illuminate\Support\Facades\File;
use Tests\TestCase;

beforeEach(function () {
    config()->set('markfolio.content_directory', resource_path('content'));
    config()->set('markfolio.default_layout', 'layouts.app');
    
    if (!File::exists(resource_path('content'))) {
        File::makeDirectory(resource_path('content'), 0755, true);
    }
});

afterEach(function () {
    if (File::exists(resource_path('content'))) {
        File::cleanDirectory(resource_path('content'));
    }
});

it('can create a basic markdown page', function () {
    $this->artisan('make:markdown-page', ['path' => 'command-test'])
        ->assertSuccessful();

    $filePath = resource_path('content/command-test.md');
    
    expect(File::exists($filePath))->toBeTrue();
    
    $content = File::get($filePath);
    
    expect($content)
        ->toContain('title: Command-Test')
        ->toContain('layout: layouts.app')
        ->toContain('# Command-Test')
        ->toContain('created_at:');
});

it('can create a markdown page with custom title and layout', function () {
    $this->artisan('make:markdown-page', [
        'path' => 'custom-test',
        '--title' => 'Custom Title',
        '--layout' => 'layouts.custom'
    ])->assertSuccessful();

    $filePath = resource_path('content/custom-test.md');
    
    expect(File::exists($filePath))->toBeTrue();
    
    $content = File::get($filePath);
    
    expect($content)
        ->toContain('title: Custom Title')
        ->toContain('layout: layouts.custom')
        ->toContain('# Custom Title');
});

it('creates directories if they dont exist', function () {
    $this->artisan('make:markdown-page', ['path' => 'nested/test/page'])
        ->assertSuccessful();

    $filePath = resource_path('content/nested/test/page.md');
    
    expect(File::exists($filePath))->toBeTrue();
});

it('prompts for confirmation when file exists', function () {
    $filePath = resource_path('content/existing.md');
    File::put($filePath, 'existing content');
    
    $this->artisan('make:markdown-page', ['path' => 'existing'])
        ->expectsQuestion("The file {$filePath} already exists. Do you want to overwrite it?", false)
        ->assertFailed();
    
    expect(File::get($filePath))->toBe('existing content');
}); 