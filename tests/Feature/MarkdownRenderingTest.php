<?php

namespace Markfolio\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Markfolio\Middleware\MarkdownRenderer;
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

it('can render markdown page', function () {
    $markdown = <<<EOT
---
title: Test Page
layout: layouts.app
---

# Test Page

This is a test page.
EOT;

    File::put(resource_path('content/test.md'), $markdown);

    $response = $this->get('/test');

    $response->assertStatus(200)
        ->assertSee('Test Page')
        ->assertSee('This is a test page');
});

it('returns 404 for nonexistent page', function () {
    $response = $this->get('/nonexistent');

    $response->assertStatus(404);
});

it('can render without layout', function () {
    $markdown = <<<EOT
---
title: No Layout Test
---

# No Layout Test

Testing without layout.
EOT;

    File::put(resource_path('content/no-layout.md'), $markdown);

    $response = $this->get('/no-layout');

    $response->assertStatus(200)
        ->assertSee('No Layout Test')
        ->assertSee('Testing without layout');
}); 