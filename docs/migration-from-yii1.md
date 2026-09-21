# Migration from Yii 1.1

This package is a **standalone port** of the Yii 1.1 validation component, not a
drop-in replacement. This document is the running log of known behavior
differences and the action required when upgrading a legacy codebase.

Newest entries go on top. If you find a difference that is not listed here,
please add an entry (or open an issue).

<!--
Template for a new entry:

## Short title
**Was (Yii1):** ...
**Now:** ...
**Action:** ...
-->

---

## Conditional rule application via `when`

**Was (Yii1):** no equivalent — a rule was either always applied or gated by `on` / `except` scenarios.

**Now:** every rule supports a `when` callable. It receives the validated object
and returns a boolean; when it returns `false`, the whole rule is skipped. It is
evaluated once per rule (not once per attribute) and only for rules that already
passed the scenario (`on` / `except`) checks.

```php
['email', 'required', 'when' => fn($model) => $model->subscribe],
```

**Action:** optional. Use it in place of manual checks inside inline validators.

---

## Inline validator method signature

**Was (Yii1):** the method received two arguments: `function ($attribute, $params)`.

**Now:** three arguments: `function ($attribute, array $params, Validator $validator)`.

```php
public function checkPassword(string $attribute, array $params, Validator $validator): void
```

**Action:** update inline validation methods to accept the third `Validator`
argument (or drop unused parameters explicitly). Rule options `on`, `except` and
`when` are no longer passed inside `$params`; any other option is.

---

## Date and time format patterns are PHP-native

**Was (Yii1):** patterns used the `CDateTimeParser` syntax, e.g. `MM/dd/yyyy`, `hh:mm`.

**Now:** patterns are native PHP `DateTime` masks, e.g. `m/d/Y`, `H:i`.

| Property | Yii1 default | This package default |
|---|---|---|
| `DateRule::$format` | `MM/dd/yyyy` | `m/d/Y` |
| `TypeRule::$dateFormat` | `MM/dd/yyyy` | `m/d/Y` |
| `TypeRule::$timeFormat` | `hh:mm` | `H:i` |
| `TypeRule::$datetimeFormat` | `MM/dd/yyyy hh:mm` | `m/d/Y H:i` |

**Action:** review and rewrite every `format` / `dateFormat` / `timeFormat` /
`datetimeFormat` string to match `DateTimeImmutable::createFromFormat()` masks.

---

## File validation uses PSR-7 `UploadedFileInterface`

**Was (Yii1):** `CFileValidator` worked with `CUploadedFile` and its `saveAs()` API.

**Now:** the `file` rule expects a `Psr\Http\Message\UploadedFileInterface`
instance (or an array of them). It reads the client filename, size, media type
and error code, but does not move the file. A non-upload value only produces an
error when `allowEmpty` is `false`.

**Action:**
- add the `psr/http-message` dependency (already a requirement of this package);
- adapt the place where uploads are received so the attribute holds
  `UploadedFileInterface` instances, not `CUploadedFile`;
- move files yourself (`UploadedFileInterface::moveTo()`) instead of relying on
  `saveAs()`.

---

## Validation target is a plain object, not `CModel`

**Was (Yii1):** validators were attached to a `CModel` via `rules()`, and
`{attribute}` was replaced with the attribute **label** from `attributeLabels()`.

**Now:** you pass any `object` and a rules array directly to the `Validator`
constructor. There is no label support — `{attribute}` is replaced with the raw
attribute name.

```php
$validator = new Validator($object, [
    ['name', 'required'],
]);

$validator->validate();
```

**Action:**
- instantiate `Validator` explicitly instead of defining `rules()` on a model;
- if you relied on human-readable labels in messages, set `message` explicitly
  (or provide labels in your own layer) — `attributeLabels()` is ignored.

---

## `CompareRule` and attribute labels

**Was (Yii1):** `CCompareValidator` resolved the compared attribute **label** through
`CModel::getAttributeLabel()` for the `{compareAttribute}` placeholder.

**Now:** there are no labels — `{compareAttribute}` is replaced with the compared
attribute **name** (e.g. `password_repeat`). `CompareRule` no longer calls
`getAttributeLabel()`, so it works on any plain object.

**Action:** if you relied on labels in the `{compareAttribute}` placeholder, provide
your own naming/translation by overriding `Validator::prepareErrorMessage()` — see
[rules.md](rules.md#attribute-names-labels-and-translation).

---

## `validate()` signature and `ValidatorInterface`

**Was (Yii1):** validation was triggered through `CModel::validate($attributes, $clearErrors)`.

**Now:** the standalone `Validator` exposes:

```php
$validator->validate(?string $scenario = null, ?array $attributes = null, bool $clearErrors = true): bool;
```

As in Yii 1, errors are cleared before validation by default; pass `false` for
`$clearErrors` to keep the existing errors. Errors are read with `getErrors()` /
`hasErrors()` and cleared manually with `clearErrors()`.

**Action:** replace `$model->validate()` calls with an explicit `Validator`
instance, and `$model->getErrors()` / `$model->hasErrors()` with the corresponding
methods on the validator. Note that the scenario is now passed explicitly as the
first argument instead of being read from the model.
