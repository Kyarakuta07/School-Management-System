# PHPUnit Tests

Testing framework untuk Mediterranean of Egypt - School Management System.

## Requirements

- PHP >= 7.4
- Composer

## Setup

```bash
# Install dependencies
composer install

# Run all tests
composer test
# OR
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# Run with coverage report
composer test:coverage
```

## Test Structure

```
tests/
├── bootstrap.php       # Test environment setup
├── TestCase.php        # Base test class
├── Unit/               # Unit tests (isolated functions)
│   ├── SanitizationTest.php
│   ├── HelpersTest.php
│   └── CsrfTest.php
└── Feature/            # Feature tests (user flows)
    └── ExampleFeatureTest.php
```

## Writing Tests

```php
<?php

namespace MOE\Tests\Unit;

use MOE\Tests\TestCase;

class MyTest extends TestCase
{
    /** @test */
    public function it_does_something(): void
    {
        $result = my_function();
        $this->assertTrue($result);
    }
}
```

## Coverage

HTML coverage reports are generated in `coverage/` directory.
