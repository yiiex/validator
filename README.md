# Yii Validation Component

This package provides the validation functionality from Yii 1.1 as a standalone component that can be used independently of the rest of the framework.

## Installation

Install via Composer:

```
composer require yii1x/validator
```

## Migration Notice

⚠️ **Migration from Yii1**  
The `DateValidator` format syntax has changed from Yii1 (`MM/dd/yyyy`) to PHP-native (`m/d/Y`).  
After upgrading, **review and update your `format` strings** to match PHP `DateTime` masks.

## License

This package is released under the BSD License, the same license used by the Yii1 framework.
