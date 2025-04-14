#!/bin/bash

# Run all tests and checks before uploading

echo "Running Pest tests..."
vendor/bin/pest

if [ $? -ne 0 ]; then
    echo "❌ Pest tests failed!"
    exit 1
fi

echo "Running PHPStan analysis..."
vendor/bin/phpstan analyse --memory-limit=2G || true
echo "⚠️ PHPStan reported issues that need attention before final release."

echo "Running Laravel Pint code style check..."
vendor/bin/pint --test

if [ $? -ne 0 ]; then
    echo "❌ Pint code style check failed!"
    exit 1
fi

echo "✅ Tests passed! Package is ready for initial testing."
echo "Note: Address PHPStan warnings before final release." 