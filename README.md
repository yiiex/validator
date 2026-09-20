# Yii1x Validator

> Yii 1.1 validation, extracted and modernized for PHP 8.3+.

Familiar validators. Zero framework lock-in.

[![Packagist](https://img.shields.io/packagist/v/yii1x/validator)](https://packagist.org/packages/yii1x/validator)
[![Total Downloads](https://img.shields.io/packagist/dt/yii1x/validator)](https://packagist.org/packages/yii1x/validator)
[![License](https://img.shields.io/packagist/l/yii1x/validator)](https://packagist.org/packages/yii1x/validator)
[![CI](https://github.com/yiiex/validator/actions/workflows/ci.yaml/badge.svg?branch=master)](https://github.com/yiiex/validator/actions/workflows/ci.yaml)

---

## What is it?

This package is the **validation** component from **Yii 1.1**, extracted and refactored to run on **PHP 8.3+** without requiring the full Yii framework.

- ✅ Same validator behavior you know from Yii 1
- ✅ PHP 8 types and strict typing
- ✅ No `CModel`, no `Yii::app()`, no global state
- ✅ Validates any plain `object`
- ✅ File uploads use PSR-7 `UploadedFileInterface`

Full reference of validators and options: **[docs/rules.md](docs/rules.md)**.

---

## Requirements

- PHP ≥ 8.3
- `psr/http-message` (used by the `file` validator)
- Optional:
    - `ext-intl` — for `validateIDN` in the `email` and `url` validators
    - `ext-mbstring` — for multibyte-aware `length` validation

---

## Installation

```bash
composer require yii1x/validator
```

---

## Usage

```php
<?php

use Yii1x\Validator\Validator;

$model = new class {
    public string $name = '';
    public string $email = 'not-an-email';
};

$validator = new Validator($model, [
    ['name', 'required'],
    ['email', 'email'],
]);

if ($validator->validate()) {
    echo 'Valid!';
} else {
    print_r($validator->getErrors());
}
```

Scenarios, conditional rules, inline validators, the full rule list and every
option are documented in **[docs/rules.md](docs/rules.md)**.

---

## Testing

Requires Docker Compose. No database is needed — the tests are pure unit tests.

```bash
# Build the image and run the whole suite
docker compose build
docker compose run --rm php

# Run a subset
docker compose run --rm php php vendor/bin/phpunit --filter When
```

Tests run automatically on **GitHub Actions** for every push and pull request to `master` — see [`.github/workflows/ci.yaml`](.github/workflows/ci.yaml).

---

## Migration from Yii 1

⚠️ This package is a **standalone port**, not a drop-in replacement. Some behavior differs from the original Yii 1.1 validators (date formats, file handling, attribute labels, and more).

Before upgrading a legacy codebase, read **[docs/migration-from-yii1.md](docs/migration-from-yii1.md)**.

---

## License

Released under the [BSD-3-Clause License](LICENSE).

Based on [Yii 1.1](https://github.com/yiisoft/yii) by Yii Software LLC
(BSD-3-Clause); portions copyright (c) 2025 Galtsev Timofey.
