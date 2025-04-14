# Markfolio

A Laravel package for rendering markdown files as pages with support for front matter, layouts, and caching.

## Features

- Render markdown files as pages
- Support for YAML front matter
- Custom layouts per page
- Built-in caching
- Laravel Folio integration
- Artisan command for creating pages

## Requirements

- PHP 8.2 or higher
- Laravel 10.x

## Installation

```bash
composer require abrudtkuhl/markfolio
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Markfolio\MarkfolioServiceProvider" --tag="config"
```

This will create a `config/markfolio.php` file with the following options:

```php
return [
    'content_directory' => resource_path('content'),
    'default_layout' => 'layouts.app',
    'cache' => [
        'enabled' => env('MARKFOLIO_CACHE_ENABLED', true),
        'ttl' => env('MARKFOLIO_CACHE_TTL', 3600),
    ],
];
```

## Usage

### Creating Pages

Use the Artisan command to create new markdown pages:

```bash
php artisan make:markdown-page about
```

This will create a file at `resources/content/about.md` with the following content:

```markdown
---
title: About
layout: layouts.app
---

# About

Your content here.
```

You can also specify a custom title and layout:

```bash
php artisan make:markdown-page about --title="About Us" --layout="layouts.custom"
```

### Writing Content

Markfolio supports YAML front matter for page metadata:

```markdown
---
title: My Page
layout: layouts.custom
author: John Doe
date: 2024-03-20
---

# My Page

This is my page content.
```

### Rendering Pages

Markfolio provides a middleware that automatically renders markdown files. Add it to your routes:

```php
Route::get('/{path}', function ($path) {
    // Your route logic
})->middleware(\Markfolio\Middleware\MarkdownRenderer::class);
```

### Laravel Folio Integration

Markfolio integrates with Laravel Folio. Simply install Folio and Markfolio will automatically handle your markdown files.

## Caching

Markfolio includes built-in caching to improve performance. By default, rendered pages are cached for 1 hour. You can configure this in the config file.

## Testing

```bash
composer test
```

## Future Enhancements

The following features are planned for future releases:

### Content Organization
- Categories/tags support
- Related content suggestions
- Content hierarchy (parent/child pages)
- Content versioning
- Draft/published states

### Table of Contents
- Auto-generate TOC from markdown headings
- Customizable depth and styling
- Anchor links for headings
- Flexible TOC placement

### Asset Management
- Image optimization and caching
- Asset aliases/paths
- Support for various media types
- Automatic responsive images
- CDN integration

### Search Functionality
- Full-text search across markdown files
- Laravel Scout integration
- Search result highlighting
- Search index auto-updating

### Syntax Highlighting
- Code block syntax highlighting
- Multiple language support
- Custom themes
- Line numbers
- Copy-to-clipboard functionality

### Performance Optimizations
- Lazy loading for images
- Content preloading
- Advanced caching strategies
- Asset bundling
- Progressive loading

### Developer Experience
- Live preview while editing
- Markdown linting
- Front matter validation
- Content validation
- Enhanced error messages

### Integration Features
- Webhook support
- API endpoints
- RSS feed generation
- Sitemap generation
- Social media preview cards

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
