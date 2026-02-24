# Yii Validation Component

A standalone validation component extracted from Yii1 framework, designed to work independently without dependencies and compatible with PHP 8.

## Features

- Standalone validation rules extracted from Yii1
- No external dependencies
- Compatible with PHP 8+
- Maintains the familiar Yii validation interface

## Installation

Install via Composer:

```
composer require your-package-name
```

## Usage

```php
use YourNamespace\Validator;

$validator = new Validator();
// Add your validation rules and validate data
```

## Migration Notice

⚠️ **Migration from Yii1**  
The `DateValidator` format syntax has changed from Yii1 (`MM/dd/yyyy`) to PHP-native (`m/d/Y`).  
After upgrading, **review and update your `format` strings** to match PHP `DateTime` masks.

## License

This package is released under the BSD License, the same license used by the Yii1 framework.