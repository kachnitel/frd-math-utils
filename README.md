# Math Utils

PHP library for mathematical operations including median, average, sum, and formula processing.

## Features

- **Basic Statistical Functions**: median, average, sum
- **Formula Processor**: Process complex mathematical formulas with nested functions and parameters
- **Secure Evaluation**: Protected against code injection attacks
- **Pure PHP**: No dependencies (except PHPUnit for development)

## Installation

```bash
composer require kachnitel/math-utils
```

## Usage

### Basic Operations

```php
use Kachnitel\MathUtils\Math;

// Calculate median
$median = Math::median([1, 2, 3, 4, 5]); // 3

// Calculate average
$average = Math::average([1, 2, 3, 4, 5]); // 3

// Calculate sum
$sum = Math::sum([1, 2, 3, 4, 5]); // 15
```

### Formula Processing

The formula processor supports nested functions, parameters, and basic arithmetic operations (+, -, *, /).

```php
use Kachnitel\MathUtils\Math;

// Simple formula
$result = Math::process('1 + 1', []); // 2

// Formula with parameters
$result = Math::process('$0 * $1', [2, 3]); // 6

// Complex nested formula
$result = Math::process(
    'average([average([sum($0), sum($1)]),median($2)]) * 2',
    [
        [1, 2, 3, 4],    // $0
        [5, 6, 7, 8],    // $1
        [10, 11, 12]     // $2
    ]
); // Calculated result

// Track calculation steps
$steps = [];
$result = Math::process('average([sum($0), sum($1)])', [[1, 2], [3, 4]], $steps);
// $steps will contain each step of the calculation
```

### Supported Functions in Formulas

- `sum(array)` - Sum of array elements
- `average(array)` - Average of array elements
- `median(array)` - Median of array elements

## Security

The formula processor is protected against code injection. All formulas are validated before evaluation:

```php
// This will throw MathException
Math::process('1 + 1; echo "injection";', []);
```

## Requirements

- PHP 8.2 or higher

## License

MIT License - see LICENSE file for details
