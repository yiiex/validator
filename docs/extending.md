# Extending the Validator

`Validator` exposes a few small protected methods that decide how rule objects are
created. Override them in a subclass to plug in a DI container, an injector, or any
other construction strategy — the core stays free of such dependencies.

## Hooks

| Method | Purpose |
|---|---|
| `createRuleInstance(string $class, object $object): AbstractRule` | Creates a rule instance for an alias or class name. Default: `new $class()`. |
| `createInlineRuleInstance(string $method, object $object, array $params): AbstractRule` | Creates the inline rule for a method defined on the validated object. |

The returned rule is then configured by the validator:

- for both kinds the validator assigns the common properties: `attributes`,
  `on` / `except` / `when` and the validator instance;
- a class rule additionally gets its options (each option is a public property of
  the rule class);
- an inline rule must have its own properties set by the hook: `method` and
  `params` (the default implementation sets both, plus `skipOnError`).

## Example: creating rules through a PSR-11 container

> The snippet below is **illustrative** — it sketches how the hook can be used with a
> container and assumes `psr/container`, which is not a dependency of this package.

```php
use Psr\Container\ContainerInterface;
use Yii1x\Validator\Rules\AbstractRule;
use Yii1x\Validator\Validator;

final class ContainerAwareValidator extends Validator
{
    public function __construct(
        object $object,
        array $rules,
        private ContainerInterface $container,
    ) {
        parent::__construct($object, $rules);
    }

    protected function createRuleInstance(string $class, object $object): AbstractRule
    {
        return $this->container->has($class)
            ? $this->container->get($class)
            : new $class();
    }
}
```

> **Note:** a rule is configured per validation (options, attributes, scenario and
> the validator instance are assigned right after creation). `createRuleInstance()`
> should return a **fresh instance** each time. If your container returns shared
> singletons, bind the rule classes as factories or reset their state — otherwise
> state leaks between validations. Managing this is entirely up to the subclass.

## Custom rule names

The `$ruleAlias` map is `protected static`. Redeclaring it in a subclass **replaces
the whole map** (late static binding), so the built-in aliases are lost — you have to
copy every alias you still need:

```php
use Yii1x\Validator\Rules\RequiredRule;
use Yii1x\Validator\Rules\SlugRule;

final class MyValidator extends Validator
{
    protected static array $ruleAlias = [
        'required' => RequiredRule::class,
        // ... all the other built-in aliases ...
        'slug' => SlugRule::class,
    ];
}
```

For one-off rules you don't need an alias at all — pass the rule class directly:

```php
$validator = new Validator($object, [
    ['slug', SlugRule::class],
]);
```
