# Contributing to Markfolio

Thank you for considering contributing to Markfolio! This document provides guidelines and instructions for contributing to this project.

## Development Setup

1. Fork the repository
2. Clone your fork locally
3. Install dependencies:
   ```bash
   composer install
   ```

## Testing with Pest

This package uses [Pest PHP](https://pestphp.com/) for testing, which provides an elegant, expressive testing API:

```php
it('can parse markdown files', function () {
    // Test logic here
    expect($result)->toBeMarkdownPage();
});
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage report
composer test-coverage
```

### Writing Tests

- Tests are located in the `tests` directory
- Unit tests go in `tests/Unit`
- Feature tests go in `tests/Feature`
- Use the `expect()` API for assertions
- Use our custom expectations like `toBeMarkdownPage()` and `toBeValidHtml()`

## Coding Standards

- Follow PSR-12 coding standards
- Don't add comments to code unless absolutely necessary
- Follow Laravel's coding style

## Pull Request Process

1. Create a feature branch from `main`
2. Make your changes
3. Add or update tests for your changes
4. Ensure all tests pass
5. Submit a pull request to the `main` branch

## Code of Conduct

Please note that this project is released with a Contributor Code of Conduct. By participating in this project you agree to abide by its terms.

## License

By contributing to Markfolio, you agree that your contributions will be licensed under the same MIT license as the project. 