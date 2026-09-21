# Rules Reference

Complete reference of every validator shipped with `yii1x/validator`, plus the
options shared by all of them.

## Table of Contents

- [Rule declaration](#rule-declaration)
- [Common options](#common-options)
- [Attribute names, labels and translation](#attribute-names-labels-and-translation)
- [Scenarios](#scenarios)
- [Conditional rules (`when`)](#conditional-rules-when)
- [required](#required)
- [filter](#filter)
- [match](#match)
- [email](#email)
- [url](#url)
- [compare](#compare)
- [length](#length)
- [in](#in)
- [numerical](#numerical)
- [type](#type)
- [file](#file)
- [default](#default)
- [boolean](#boolean)
- [safe](#safe) / [unsafe](#unsafe)
- [date](#date)
- [Safe & required attributes](#safe--required-attributes)
- [Inline validators](#inline-validators)
- [Custom rule classes](#custom-rule-classes)

---

## Rule declaration

Rules are passed as an array to the `Validator` constructor. Each rule is an
array where:

- element `0` — attribute name(s): a string (`'name'`, `'name, email'`) or an array;
- element `1` — validator name: an alias from the table below, an inline method
  name, or a fully-qualified class name;
- the rest — option name/value pairs assigned to public properties of the rule. An
  option that does not match a declared property of the rule throws an
  `InvalidArgumentException`.

```php
use Yii1x\Validator\Validator;

$validator = new Validator($object, [
    ['name', 'required'],
    ['name, email', 'filter', 'filter' => 'trim'],
    ['age', 'numerical', 'integerOnly' => true, 'min' => 18],
]);

$validator->validate();               // default scenario: rules without "on"
$validator->validate('insert');       // rules without "on" and rules with "on" => "insert"
$validator->validate(null, ['age']);  // validate only the listed attributes

$validator->getErrors();              // all errors
$validator->getErrors('age');         // errors for one attribute
$validator->hasErrors('age');         // bool
$validator->clearErrors();            // reset
```

---

## Common options

Every rule extends `AbstractRule` and therefore supports the following options.

| Property | Type | Default | Description |
|---|---|---|---|
| `attributes` | `array` | `[]` | Attributes to validate. Filled from element `0` of the rule. |
| `message` | `?string` | `null` | Custom error message. Supports `{attribute}` and rule-specific placeholders. |
| `skipOnError` | `bool` | `false` | Skip the attribute when it already has an error from a previous rule. |
| `on` | `array\|string` | `[]` | Scenarios the rule applies to (comma/space separated or array). |
| `except` | `array\|string` | `[]` | Scenarios the rule does **not** apply to. |
| `safe` | `bool` | `true` | Whether the attributes are considered safe for mass assignment. |
| `when` | `?callable` | `null` | `fn($object): bool`. When it returns `false`, the whole rule is skipped. Evaluated last, after the scenario checks, once per `getRules()` call. |

For every rule, `{attribute}` is replaced with the **attribute name**. This package
does not resolve attribute labels or translations — see
[Attribute names, labels and translation](#attribute-names-labels-and-translation)
for how to customise this. A `null` or empty-string scenario is treated as the Yii 1
default (empty) scenario: rules with a non-empty `on` are skipped.

---

## Attribute names, labels and translation

Error messages use the raw attribute name for `{attribute}` (and, in `compare`, for
`{compareAttribute}`). There are no built-in attribute labels or translations.

If you need human-readable names, translation, or an extra `{label}` placeholder,
override `Validator::prepareErrorMessage()`. It is the single place where every error
message is prepared, and the validated object is available as `$this->object`. In this
method you can resolve labels, translate messages and transform any placeholders (for
example, map `{compareAttribute}` to a label).

---

## Scenarios

Rules can be limited to specific scenarios with `on` / `except`:

```php
$validator = new Validator($model, [
    ['name', 'required', 'on' => 'insert'],
    ['email', 'email', 'except' => 'update'],
]);

$validator->validate('insert');
```

`on` and `except` accept either a comma/space separated string (`'insert, update'`)
or an array. A rule applies when its `on` list is empty or contains the scenario,
and the scenario is not present in its `except` list.

Following Yii 1 semantics, a model always has a scenario and the default one is an
empty string. A missing scenario (`null`) is therefore treated as `''`: **only
rules without `on` apply**, while rules restricted to a named scenario are skipped.

```php
$validator->validate();          // default (empty) scenario: rules with "on" are skipped
$validator->validate('insert');  // rules with "on" => "insert" (and rules without "on")
```

`validate()` also accepts an attribute filter:

```php
$validator->validate('insert', ['name']);   // only the "name" attribute
```

---

## Conditional rules (`when`)

Any rule accepts a `when` callable. It receives the object being validated and
must return a boolean; when it returns `false`, the whole rule is skipped.

```php
$validator = new Validator($model, [
    ['email', 'required', 'when' => fn($model) => $model->subscribe],
]);
```

`when` is evaluated **last** — after the `on` / `except` scenario checks — once per
`getRules()` call, i.e. per `validate()`, `getSafeAttributes()` and
`getRequiredAttributes()`. A rule excluded by the scenario never reaches the `when`
check, so the callable is not invoked for it.

---

## required

**Alias:** `required` · **Class:** `Yii1x\Validator\Rules\RequiredRule`

Checks that the attribute is not empty. A value is empty when it is `null`, an
empty array, or (after optional trimming) an empty string. `0` is **not** empty.

```php
['name', 'required'],
['role', 'required', 'requiredValue' => 'admin', 'strict' => true],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `requiredValue` | `mixed` | `null` | If set, the attribute must equal this value instead of merely being non-empty. |
| `strict` | `bool` | `false` | Strict comparison against `requiredValue`. |
| `trim` | `bool` | `true` | Trim strings before the emptiness check. |

**Messages**
- default: `{attribute} cannot be blank.`
- with `requiredValue`: `{attribute} must be {value}.` — `{value}` = `requiredValue`.

---

## filter

**Alias:** `filter` · **Class:** `Yii1x\Validator\Rules\FilterRule`

Not a validator — transforms the attribute value and writes it back. Any callable
accepting one argument qualifies (`trim`, `strtolower`, a closure, …).

```php
['email', 'filter', 'filter' => 'trim'],
['name', 'filter', 'filter' => 'ucfirst'],
['slug', 'filter', 'filter' => fn($v) => preg_replace('/[^a-z0-9]+/', '-', strtolower($v))],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `filter` | `?callable` | `null` | The filter callback. **Required** — throws `InvalidArgumentException` if missing or not callable. |

No error message is produced; the return value replaces the attribute.

---

## match

**Alias:** `match` · **Class:** `Yii1x\Validator\Rules\RegularExpressionRule`

Validates the value against a PCRE pattern.

```php
['username', 'match', 'pattern' => '/^[a-z0-9_]+$/i'],
['code', 'match', 'pattern' => '/^\d+$/', 'not' => true],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `pattern` | `?string` | `null` | Regular expression. **Required** — throws if `null`. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `not` | `bool` | `false` | Invert the logic: the pattern must **not** match. |

**Message:** `{attribute} is invalid.`

---

## email

**Alias:** `email` · **Class:** `Yii1x\Validator\Rules\EmailRule`

Validates an email address.

```php
['email', 'email'],
['email', 'email', 'allowName' => true, 'checkMX' => true, 'validateIDN' => true],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `pattern` | `string` | built-in regexp | Pattern for a plain address. |
| `fullPattern` | `string` | built-in regexp | Pattern used when `allowName` is `true`. |
| `allowName` | `bool` | `false` | Allow `Name <user@example.com>` form. |
| `checkMX` | `bool` | `false` | Check MX record (`checkdnsrr`). |
| `checkPort` | `bool` | `false` | Try to open port 25 on the MX hosts. |
| `timeout` | `?int` | `null` | Socket timeout for `checkPort`; defaults to `default_socket_timeout`. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `validateIDN` | `bool` | `false` | Encode internationalized domains via `idn_to_ascii()` (requires `ext-intl`). |

**Message:** `{attribute} is not a valid email address.`

---

## url

**Alias:** `url` · **Class:** `Yii1x\Validator\Rules\UrlRule`

Validates an http/https URL. On success the attribute is written back (e.g. with
`defaultScheme` prepended).

```php
['site', 'url'],
['site', 'url', 'defaultScheme' => 'https'],
['site', 'url', 'validSchemes' => ['http', 'https'], 'validateIDN' => true],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `pattern` | `string` | Yii 1 default | Regular expression that the value must match. The `{schemes}` token is replaced with an alternation of `validSchemes`. |
| `validSchemes` | `array` | `['http', 'https']` | Allowed URI schemes, substituted into `{schemes}`. |
| `defaultScheme` | `?string` | `null` | Scheme prepended when the value has no `://` part. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `validateIDN` | `bool` | `false` | Encode internationalized hosts via `idn_to_ascii()` (requires `ext-intl`). |

**Message:** `{attribute} is not a valid URL.`

---

## compare

**Alias:** `compare` · **Class:** `Yii1x\Validator\Rules\CompareRule`

Compares the attribute with another attribute or a constant value.

```php
['password', 'compare'],                                      // compares with password_repeat
['password', 'compare', 'compareAttribute' => 'password2'],
['age', 'compare', 'compareValue' => 18, 'operator' => '>='],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `compareAttribute` | `?string` | `null` | Attribute to compare with. Defaults to `{attribute}_repeat`. |
| `compareValue` | `mixed` | `null` | Constant value to compare with. Takes precedence over `compareAttribute`. |
| `strict` | `bool` | `false` | Strict comparison (value and type). |
| `allowEmpty` | `bool` | `false` | Empty values are considered valid. |
| `operator` | `string` | `'='` | One of `=`, `==`, `!=`, `>`, `>=`, `<`, `<=`. |

**Placeholders:** `{compareAttribute}`, `{compareValue}`.

**Messages** (per operator): `{attribute} must be repeated exactly.`,
`{attribute} must not be equal to "{compareValue}".`,
`{attribute} must be greater than "{compareValue}".`, `… greater than or equal …`,
`… less than …`, `… less than or equal …`.

> `{compareAttribute}` is replaced with the **attribute name**, not a label — see
> [Attribute names, labels and translation](#attribute-names-labels-and-translation).

---

## length

**Alias:** `length` · **Class:** `Yii1x\Validator\Rules\StringRule`

Validates string length. Uses `mb_strlen()` when available and `encoding` is not
`false`.

```php
['name', 'length', 'min' => 2, 'max' => 32],
['code', 'length', 'is' => 6],
['name', 'length', 'min' => 2, 'encoding' => 'UTF-8'],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `min` | `?int` | `null` | Minimum length. |
| `max` | `?int` | `null` | Maximum length. |
| `is` | `?int` | `null` | Exact length. |
| `tooShort` | `?string` | `null` | Message for `min` violation. |
| `tooLong` | `?string` | `null` | Message for `max` violation. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `encoding` | `null\|false\|string` | `null` | `mb_strlen()` encoding; `false` forces byte-based `strlen()`. |

**Placeholders:** `{min}`, `{max}`, `{length}`.

**Messages:** `{attribute} is too short (minimum is {min} characters).`,
`{attribute} is too long (maximum is {max} characters).`,
`{attribute} is of the wrong length (should be {length} characters).`,
and `{attribute} is invalid.` for array values.

---

## in

**Alias:** `in` · **Class:** `Yii1x\Validator\Rules\RangeRule`

Validates that the value is among a list.

```php
['tag', 'in', 'range' => ['php', 'mysql', 'jquery']],
['role', 'in', 'range' => ['user', 'admin'], 'not' => true],
['status', 'in', 'range' => [1, 2, 3], 'strict' => true],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `range` | `array` | — | List of allowed values. **Required.** |
| `strict` | `bool` | `false` | Strict comparison. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `not` | `bool` | `false` | Invert: the value must **not** be in the list. |

**Messages:** `{attribute} is not in the list.` / `{attribute} is in the list.` (`not`).

---

## numerical

**Alias:** `numerical` · **Class:** `Yii1x\Validator\Rules\NumberRule`

Validates a numeric value, optionally with bounds.

```php
['age', 'numerical', 'integerOnly' => true, 'min' => 18, 'max' => 120],
['price', 'numerical', 'min' => 0],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `integerOnly` | `bool` | `false` | Only integers are allowed. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `min` | `int\|float\|null` | `null` | Lower bound. |
| `max` | `int\|float\|null` | `null` | Upper bound. |
| `tooSmall` | `?string` | `null` | Custom message for `min` violation. |
| `tooBig` | `?string` | `null` | Custom message for `max` violation. |
| `integerPattern` | `string` | built-in regexp | Integer format pattern. |
| `numberPattern` | `string` | built-in regexp | Number format pattern. |

**Placeholders:** `{min}`, `{max}`.

**Messages:** `{attribute} must be a number.`, `{attribute} must be an integer.`,
`{attribute} is too small (minimum is {min}).`, `{attribute} is too big (maximum is {max}).`

---

## type

**Alias:** `type` · **Class:** `Yii1x\Validator\Rules\TypeRule`

Validates that the value is of a given type. Supported types: `string`, `integer`,
`float`, `array`, `date`, `time`, `datetime`.

```php
['age', 'type', 'type' => 'integer'],
['created_at', 'type', 'type' => 'datetime', 'datetimeFormat' => 'Y-m-d H:i:s'],
['meta', 'type', 'type' => 'array'],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `type` | `string` | `'string'` | Expected type. |
| `dateFormat` | `string` | `'m/d/Y'` | PHP `DateTime` mask for the `date` type. |
| `timeFormat` | `string` | `'H:i'` | PHP `DateTime` mask for the `time` type. |
| `datetimeFormat` | `string` | `'m/d/Y H:i'` | PHP `DateTime` mask for the `datetime` type. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `strict` | `bool` | `false` | Require the actual PHP type (no numeric-string coercion). |

**Message:** `{attribute} must be {type}.`

> ⚠️ Date/time masks use **PHP native** syntax (`m/d/Y`), not the Yii1
> `CDateTimeParser` syntax. See [migration-from-yii1.md](migration-from-yii1.md).

---

## file

**Alias:** `file` · **Class:** `Yii1x\Validator\Rules\FileRule`

Validates uploaded files represented as PSR-7
`Psr\Http\Message\UploadedFileInterface` instances. The attribute can hold a
single instance or an array of instances. The rule does not move files.

```php
['avatar', 'file', 'types' => ['jpg', 'png', 'gif'], 'maxSize' => 2 * 1024 * 1024],
['documents', 'file', 'maxFiles' => 5, 'allowEmpty' => true],
['attachment', 'file', 'mimeTypes' => ['application/pdf']],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `allowEmpty` | `bool` | `false` | Allow no file to be uploaded. When empty and `safe`, the attribute is set to `null`. |
| `types` | `?array` | `null` | Allowed file extensions. The filename's extension is lowercased before a strict comparison, so list lowercase values (`['jpg', 'png']`). |
| `mimeTypes` | `?array` | `null` | Allowed MIME types. The file's media type is lowercased before a strict comparison, so list lowercase values (`['image/jpeg']`). |
| `minSize` | `?int` | `null` | Minimum size in bytes. |
| `maxSize` | `?int` | `null` | Maximum size in bytes (also capped by `upload_max_filesize` / `post_max_size`). |
| `maxFiles` | `int` | `1` | Maximum number of files. |
| `tooLarge` | `string` | `The file "{file}" is too large. Its size cannot exceed {limit} bytes.` | Message for oversized file. |
| `tooSmall` | `string` | `The file "{file}" is too small. Its size cannot be smaller than {limit} bytes.` | Message for undersized file. |
| `wrongType` | `string` | `The file "{file}" cannot be uploaded. Only files with these extensions are allowed: {extensions}.` | Message for a disallowed extension. |
| `wrongMimeType` | `string` | `The file "{file}" cannot be uploaded. Only files of these MIME-types are allowed: {mimeTypes}.` | Message for a disallowed MIME type. |
| `tooMany` | `string` | `{attribute} cannot accept more than {limit} files.` | Message for too many files. |
| `message` | `?string` | `{attribute} cannot be blank.` | Message when no file is provided and `allowEmpty` is `false`. |

Additionally, per-upload error codes (`UPLOAD_ERR_*`) produce their own messages.

---

## default

**Alias:** `default` · **Class:** `Yii1x\Validator\Rules\DefaultValueRule`

Not a validator — assigns a default value to the attribute during validation.

```php
['status', 'default', 'value' => 'draft'],
['count', 'default', 'value' => 0, 'setOnEmpty' => false],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `value` | `mixed` | `null` | Value to assign. |
| `setOnEmpty` | `bool` | `true` | Assign only when the current value is `null` or `''`. When `false`, always overwrite. |

No error message is produced.

---

## boolean

**Alias:** `boolean` · **Class:** `Yii1x\Validator\Rules\BooleanRule`

Validates that the value equals the configured true/false representations.

```php
['active', 'boolean'],
['active', 'boolean', 'trueValue' => 1, 'falseValue' => 0, 'strict' => true],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `trueValue` | `mixed` | `'1'` | Value representing `true`. |
| `falseValue` | `mixed` | `'0'` | Value representing `false`. |
| `strict` | `bool` | `false` | Strict comparison. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |

**Placeholders:** `{true}`, `{false}`.
**Message:** `{attribute} must be either {true} or {false}.`

---

## safe

**Alias:** `safe` · **Class:** `Yii1x\Validator\Rules\SafeRule`

Marks its attributes as safe for mass assignment. Performs no validation.

```php
['title, body', 'safe'],
```

## unsafe

**Alias:** `unsafe` · **Class:** `Yii1x\Validator\Rules\UnsafeRule`

Marks its attributes as **unsafe** for mass assignment (`safe = false`). Performs
no validation. Use `Validator::getSafeAttributes()` to read the resulting set.

```php
['is_admin', 'unsafe'],
```

---

## date

**Alias:** `date` · **Class:** `Yii1x\Validator\Rules\DateRule`

Validates a date/time/datetime value against one or more PHP `DateTime` masks.

```php
['birthday', 'date', 'format' => 'Y-m-d'],
['created_at', 'date', 'format' => ['Y-m-d', 'Y-m-d H:i:s'], 'timestampAttribute' => 'created_ts'],
```

| Property | Type | Default | Description |
|---|---|---|---|
| `format` | `string\|array` | `'m/d/Y'` | One or more PHP `DateTime` masks. |
| `allowEmpty` | `bool` | `true` | Empty values are considered valid. |
| `timestampAttribute` | `?string` | `null` | Attribute that receives the parsed Unix timestamp on success. |

**Message:** `The format of {attribute} is invalid.`

> ⚠️ `format` uses **PHP native** masks (`m/d/Y`), not Yii1 `MM/dd/yyyy`.
> See [migration-from-yii1.md](migration-from-yii1.md).

---

## Safe & required attributes

The validator can report which attributes are considered safe for mass assignment
and which are required, taking scenarios into account:

```php
$required = $validator->getRequiredAttributes();      // attributes marked by the "required" rule
$required = $validator->getRequiredAttributes('insert');

$safe = $validator->getSafeAttributes();              // attributes safe for mass assignment
$safe = $validator->getSafeAttributes('insert');
```

`safe` rules add attributes to the safe list, `unsafe` rules remove them, and any
other rule marks its attributes safe unless its `safe` property is set to `false`.

---

## Inline validators

When the rule name matches a method on the validated object, an inline validator
is used. The method receives the attribute name, extra options and the validator:

```php
$model = new class {
    public string $password = '';
    public string $password_repeat = '';

    public function checkPassword(string $attribute, array $params, Validator $validator): void
    {
        if ($this->password !== $this->password_repeat) {
            $validator->addError($attribute, 'Passwords do not match.');
        }
    }
};

$validator = new Validator($model, [
    ['password', 'checkPassword'],
]);
```

Any rule option except `on`, `except` and `when` is passed to the method in
`$params`.

---

## Custom rule classes

Any class extending `AbstractRule` can be used by its fully-qualified name:

```php
use Yii1x\Validator\Rules\AbstractRule;

class SlugRule extends AbstractRule
{
    protected function validateAttribute(object $object, string $attribute): void
    {
        if (!preg_match('/^[a-z0-9-]+$/', $object->$attribute)) {
            $this->validator->addError($attribute, $this->message ?? '{attribute} is not a valid slug.');
        }
    }
}

$validator = new Validator($object, [
    ['slug', SlugRule::class, 'message' => 'Invalid slug.'],
]);
```

Public properties are injected from the rule array, so custom rules can accept
their own options the same way built-in rules do.
