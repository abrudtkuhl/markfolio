<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Markfolio\Middleware\MarkdownRenderer;

beforeEach(function () {
    config()->set('markfolio.content_directory', resource_path('content'));
    config()->set('markfolio.default_layout', 'layouts.app');

    View::shouldReceive('make')
        ->andReturnUsing(function ($view, $data) {
            return new Response($data['content'] ?? '');
        });
});

it('passes through non-markdown files', function () {
    $middleware = new MarkdownRenderer;
    $request = Request::create('/not-a-markdown');
    $response = new Response('original');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    expect($result)->toBe($response);
});

it('processes markdown files', function () {
    $markdown = <<<'EOT'
---
title: Test Page
layout: layouts.app
---

# Test Page

This is a test page.
EOT;

    File::put(resource_path('content/test.md'), $markdown);

    $middleware = new MarkdownRenderer;
    $request = Request::create('/test');

    $response = $middleware->handle($request, function () {
        return new Response('original');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toContain('Test Page');
});

it('uses custom layout from front matter', function () {
    $markdown = <<<'EOT'
---
title: Custom Layout
layout: layouts.custom
---

# Custom Layout

This is a test page with custom layout.
EOT;

    File::put(resource_path('content/custom-layout.md'), $markdown);

    $middleware = new MarkdownRenderer;
    $request = Request::create('/custom-layout');

    $response = $middleware->handle($request, function () {
        return new Response('original');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toContain('Custom Layout');
});

it('respects cache configuration', function () {
    config()->set('markfolio.cache.enabled', true);
    config()->set('markfolio.cache.ttl', 60);

    $markdown = <<<'EOT'
---
title: Cache Test
---

# Cache Test

This is a test page for caching.
EOT;

    File::put(resource_path('content/cache-test.md'), $markdown);

    $middleware = new MarkdownRenderer;
    $request = Request::create('/cache-test');

    $response = $middleware->handle($request, function () {
        return new Response('original');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toContain('Cache Test');
});

it('renders pages without layout specified', function () {
    $markdown = <<<'EOT'
---
title: No Layout Page
---

# No Layout Page

This is a test page without a layout.
EOT;

    File::put(resource_path('content/no-layout.md'), $markdown);

    $middleware = new MarkdownRenderer;
    $request = Request::create('/no-layout');

    $response = $middleware->handle($request, function () {
        return new Response('original');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toContain('No Layout Page');
});
