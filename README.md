# Yii1 Validator — Standalone Validation Component

[![Latest Stable Version](https://img.shields.io/packagist/v/yii1x/validator)](https://packagist.org/packages/yii1x/validator)
[![Total Downloads](https://img.shields.io/packagist/dt/yii1x/validator)](https://packagist.org/packages/yii1x/validator)
[![License](https://img.shields.io/github/license/yii1x/validator)](https://github.com/yii1x/validator/blob/master/LICENSE)

This package provides the validation functionality from Yii 1.1 as a standalone component.  
It can be used independently of the rest of the framework — perfect for legacy projects or when you need Yii-style validation in modern PHP applications.

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
