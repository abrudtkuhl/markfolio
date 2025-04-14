#!/bin/bash

# Run all tests and checks before uploading

echo "Running Pest tests..."
vendor/bin/pest

if [ $? -ne 0 ]; then
    echo "❌ Pest tests failed!"
    exit 1
fi

echo "Running PHPStan analysis..."
vendor/bin/phpstan analyse --memory-limit=2G

if [ $? -ne 0 ]; then
    echo "❌ PHPStan analysis failed!"
    exit 1
fi

echo "Running Laravel Pint code style check..."
vendor/bin/pint --test

if [ $? -ne 0 ]; then
    echo "❌ Pint code style check failed!"
    exit 1
fi

echo "✅ All tests passed! Package is ready for publishing." 