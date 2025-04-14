<?php

use Markfolio\PageParser;

it('can parse a markdown file', function () {
    $filePath = __DIR__.'/../fixtures/content/test-page.md';
    $parser = new PageParser($filePath);

    expect($parser)->toBeObject();
});

it('extracts front matter correctly', function () {
    $filePath = __DIR__.'/../fixtures/content/test-page.md';
    $parser = new PageParser($filePath);
    $data = $parser->toViewData();

    expect($data)
        ->toBeArray()
        ->toHaveKey('title', 'Test Page')
        ->toHaveKey('meta')
        ->and($data['meta'])
        ->toHaveKey('layout', 'layouts.app')
        ->toHaveKey('author', 'Test Author');

    // If the date is stored as a timestamp, allow either format
    $createdAt = $data['meta']['created_at'] ?? null;
    if (is_numeric($createdAt)) {
        expect(date('Y-m-d', $createdAt))->toBe('2023-01-01');
    } else {
        expect($createdAt)->toBe('2023-01-01');
    }
});

it('converts markdown to html correctly', function () {
    $filePath = __DIR__.'/../fixtures/content/test-page.md';
    $parser = new PageParser($filePath);
    $data = $parser->toViewData();

    expect($data)
        ->toHaveKey('content');

    $content = $data['content'];
    if (is_string($content)) {
        expect($content)
            ->toContain('<h1>Test Page</h1>')
            ->toContain('<ul>')
            ->toContain('<li>Feature 1</li>')
            ->toContain('<pre>')
            ->toContain('This is a code block');
    } elseif (is_object($content) && method_exists($content, 'getContent')) {
        $htmlContent = $content->getContent();
        expect($htmlContent)
            ->toBeString()
            ->toContain('<h1>Test Page</h1>')
            ->toContain('<ul>')
            ->toContain('<li>Feature 1</li>')
            ->toContain('<pre>')
            ->toContain('This is a code block');
    }
});

it('handles missing front matter fields gracefully', function () {
    // Create a test file with minimal front matter
    $path = 'minimal.md';
    createMarkdownFile($path, 'Minimal Page', '# Minimal Content', []);

    $filePath = __DIR__.'/../fixtures/content/'.$path;
    $parser = new PageParser($filePath);
    $data = $parser->toViewData();

    expect($data)
        ->toHaveKey('title', 'Minimal Page')
        ->toHaveKey('meta')
        ->and($data['meta'])
        ->toHaveKey('layout', 'layouts.app')
        ->not->toHaveKey('author');
});

it('processes complex metadata correctly', function () {
    // Create a test file with complex metadata
    $path = 'complex.md';
    createMarkdownFile($path, 'Complex Page', '# Complex Content', [
        'tags' => ['tag1', 'tag2', 'tag3'],
        'published' => true,
        'count' => 42,
        'nested' => [
            'key1' => 'value1',
            'key2' => 'value2',
        ],
    ]);

    $filePath = __DIR__.'/../fixtures/content/'.$path;
    $parser = new PageParser($filePath);
    $data = $parser->toViewData();

    expect($data)
        ->toHaveKey('meta')
        ->and($data['meta'])
        ->toHaveKey('tags')
        ->toHaveKey('published')
        ->toHaveKey('count');
});
