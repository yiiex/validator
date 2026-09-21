<?php
/**
 * CValidator class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator;

use Yii1x\Validator\Rules\{AbstractRule,
    BooleanRule,
    CompareRule,
    DateRule,
    DefaultValueRule,
    EmailRule,
    FileRule,
    FilterRule,
    InlineRule,
    NumberRule,
    RangeRule,
    RegularExpressionRule,
    RequiredRule,
    SafeRule,
    StringRule,
    TypeRule,
    UnsafeRule,
    UrlRule
};
use Yii1x\Validator\Contracts\ValidatorInterface;

class Validator implements ValidatorInterface
{
    protected static array $ruleAlias = [
        'required' => RequiredRule::class,
        'filter' => FilterRule::class,
        'match' => RegularExpressionRule::class,
        'email' => EmailRule::class,
        'url' => UrlRule::class,
        'compare' => CompareRule::class,
        'length' => StringRule::class,
        'in' => RangeRule::class,
        'numerical' => NumberRule::class,
        'type' => TypeRule::class,
        'file' => FileRule::class,
        'default' => DefaultValueRule::class,
        'boolean' => BooleanRule::class,
        'safe' => SafeRule::class,
        'unsafe' => UnsafeRule::class,
        'date' => DateRule::class,
    ];

    protected array $errors = [];
    protected array $_rules = [];

    public function __construct(protected object $object, protected array $rules = [])
    {

    }

    public function validate(?string $scenario = null, ?array $attributes = null, bool $clearErrors = true): bool
    {
        if ($clearErrors) {
            $this->clearErrors();
        }
        foreach ($this->getRules($scenario) as $rule) {
            $rule->validate($this->object, $attributes);
        }
        return !$this->hasErrors();
    }

    /**
     * @param string|null $scenario
     * @return AbstractRule[]
     */
    protected function getRules(?string $scenario = null): iterable
    {
        if (!$this->_rules && $this->rules) {
            $this->_rules = $this->createValidators($this->object);
        }
        foreach ($this->_rules as $rule) {
            if ($rule->applyTo($this->object, $scenario)) {
                yield $rule;
            }
        }
    }

    protected function createValidators(object $object): array
    {
        $rules = [];
        foreach ($this->rules as $rule) {
            if (isset($rule[0], $rule[1])) {
                $rules[] = $this->createValidator($rule[1], $object, $rule[0], array_slice($rule, 2));
            } else {
                throw new \InvalidArgumentException(get_class($object) . ' has an invalid validation rule. The rule must specify attributes to be validated and the validator name.');
            }
        }
        return $rules;
    }

    protected function createValidator($name, object $object, $attributes, $params = [])
    {
        if (is_string($attributes))
            $attributes = preg_split('/\s*,\s*/', trim($attributes, " \t\n\r\0\x0B,"), -1, PREG_SPLIT_NO_EMPTY);

        $on = match (true) {
            isset($params['on']) && is_array($params['on']) => $params['on'],
            isset($params['on']) => preg_split('/[\s,]+/', $params['on'], -1, PREG_SPLIT_NO_EMPTY),
            default => [],
        };
        $except = match (true) {
            isset($params['except']) && is_array($params['except']) => $params['except'],
            isset($params['except']) => preg_split('/[\s,]+/', $params['except'], -1, PREG_SPLIT_NO_EMPTY),
            default => [],
        };
        $when = $params['when'] ?? null;
        unset($params['on'], $params['except'], $params['when']);
        if ($when !== null && !is_callable($when)) {
            throw new \InvalidArgumentException('The "when" property must be a callable.');
        }
        if (isset(static::$ruleAlias[$name]) || class_exists($name)) {
            $rule = $this->createRuleInstance(static::$ruleAlias[$name] ?? $name);
            foreach ($params as $property => $value) {
                if (!property_exists($rule, $property)) {
                    throw new \InvalidArgumentException(sprintf('Unknown option "%s" for rule %s.', $property, $rule::class));
                }
                $rule->$property = $value;
            }
        } elseif (method_exists($object, $name)) {
            $rule = $this->createInlineRuleInstance($name, $params);
        } else {
            throw new \InvalidArgumentException('The rule "' . $name . '" does not exist.');
        }
        $rule->attributes = $attributes;
        $rule->on = empty($on) ? [] : array_combine($on, $on);
        $rule->except = empty($except) ? [] : array_combine($except, $except);
        $rule->when = $when;
        $rule->validator = $this;
        return $rule;
    }

    /**
     * Creates a rule instance for the given class.
     *
     * Override this method in a subclass to build rules through a DI container or an
     * injector. The returned rule is configured by the caller afterwards (the rule
     * options, attributes, "on"/"except"/"when" and the validator instance), so
     * implementations should return a fresh instance unless they manage the rule's
     * state themselves.
     *
     * @param string $class the rule class name
     * @return AbstractRule the rule instance
     */
    protected function createRuleInstance(string $class): AbstractRule
    {
        return new $class();
    }

    /**
     * Creates an inline rule (a method defined on the validated object).
     *
     * The returned rule must have its inline-specific properties configured: "method"
     * and "params" (the default implementation sets both, plus "skipOnError"). The
     * caller then assigns the common properties: attributes, "on"/"except"/"when" and
     * the validator instance.
     *
     * @param string $method the method name
     * @param array $params rule options passed to the method
     * @return AbstractRule the rule instance
     */
    protected function createInlineRuleInstance(string $method, array $params): AbstractRule
    {
        $rule = new InlineRule();
        $rule->method = $method;
        $rule->params = $params;
        if (isset($params['skipOnError'])) {
            $rule->skipOnError = $params['skipOnError'];
        }
        return $rule;
    }

    protected function prepareErrorMessage(string $attribute, string $message, array $params = []): string
    {
        return strtr($message, ['{attribute}' => $attribute] + $params);
    }

    public function addError(string $attribute, string $message, array $params = []): static
    {
        $this->errors[$attribute][] = $this->prepareErrorMessage($attribute, $message, $params);
        return $this;
    }


    public function hasErrors(null|string|array $attribute = null): bool
    {
        if ($attribute === null) {
            return $this->errors !== [];
        }
        foreach ((array)$attribute as $attr) {
            if (isset($this->errors[$attr])) {
                return true;
            }
        }
        return false;
    }

    public function getErrors(null|string|array $attribute = null): array
    {
        if ($attribute === null) {
            return $this->errors;
        }
        $result = [];
        foreach ((array)$attribute as $attr) {
            if (isset($this->errors[$attr])) {
                $result[$attr] = $this->errors[$attr];
            }
        }
        return $result;
    }

    public function clearErrors(null|string|array $attribute = null): static
    {
        if ($attribute === null) {
            $this->errors = [];
        } else {
            foreach ((array)$attribute as $attr) {
                unset($this->errors[$attr]);
            }
        }
        return $this;
    }

    public function getRequiredAttributes(?string $scenario = null): array
    {
        $requiredAttributes = [];
        foreach ($this->getRules($scenario) as $rule) {
            if ($rule instanceof RequiredRule && $rule->attributes) {
                $requiredAttributes = array_merge($requiredAttributes, $rule->attributes);
            }
        }
        return $requiredAttributes;
    }

    public function getSafeAttributes(?string $scenario = null): array
    {
        $safe = $unsafe = [];
        foreach ($this->getRules($scenario) as $rule) {
            if (!$rule->safe) {
                $unsafe = array_merge($unsafe, $rule->attributes);
            } else {
                foreach ($rule->attributes as $name) {
                    $safe[$name] = true;
                }
            }
        }
        foreach ($unsafe as $name)
            unset($safe[$name]);
        return array_keys($safe);
    }

    public function setRules(array $rules): static
    {
        if ($this->rules !== $rules) {
            $this->rules = $rules;
            $this->_rules = $this->createValidators($this->object);
        }
        return $this;
    }

}

