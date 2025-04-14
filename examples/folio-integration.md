---
title: Folio Integration Example
layout: layouts.app
---

# Integrating Markdown with Folio

This example demonstrates how Markfolio integrates Markdown content with Laravel Folio.

## How It Works

1. Laravel Folio looks for files in registered directories
2. When a Markdown file is found, the Markfolio middleware processes it
3. The middleware parses the Markdown and renders it with Blade

## Code Example

Here's a simple PHP example:

```php
// This will be rendered as code
$user = User::find(1);
echo $user->name;
```

## Benefits

- **Simplicity**: Write content in Markdown instead of PHP/Blade
- **Front Matter**: Add metadata to your pages easily
- **Flexibility**: Use custom layouts for different sections 