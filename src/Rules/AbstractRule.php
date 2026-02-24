<?php

namespace Yii1x\Validator\Rules;

use Yii1x\Validator\Validator;

abstract class AbstractRule
{
    public Validator $validator;
    /**
     * @var array list of attributes to be validated.
     */
    public array $attributes = [];
    /**
     * @var null|string the user-defined error message. Different validators may define various
     * placeholders in the message that are to be replaced with actual values. All validators
     * recognize "{attribute}" placeholder, which will be replaced with the label of the attribute.
     */
    public ?string $message = null;
    /**
     * @var boolean whether this validation rule should be skipped when there is already a validation
     * error for the current attribute. Defaults to false.
     * @since 1.1.1
     */
    public bool $skipOnError = false;
    /**
     * @var array list of scenarios that the validator should be applied.
     * Each array value refers to a scenario name with the same name as its array key.
     */
    public array $on = [];
    /**
     * @var array list of scenarios that the validator should not be applied to.
     * Each array value refers to a scenario name with the same name as its array key.
     * @since 1.1.11
     */
    public array $except = [];
    /**
     * @var boolean whether attributes listed with this validator should be considered safe for massive assignment.
     * Defaults to true.
     * @since 1.1.4
     */
    public bool $safe = true;

    abstract protected function validateAttribute(object $object, string $attribute): void;

    /**
     * Validates the specified object.
     * @param object $object $object the data object being validated
     * @param array|null $attributes the list of attributes to be validated. Defaults to null,
     * meaning every attribute listed in {@link attributes} will be validated.
     */
    public function validate(object $object, ?array $attributes = null): void
    {
        if (is_array($attributes)) {
            $attributes = array_intersect($this->attributes, $attributes);
        } else {
            $attributes = $this->attributes;
        }

        foreach ($attributes as $attribute) {
            if (!$this->skipOnError || !$this->validator->hasErrors($attribute)) {
                $this->validateAttribute($object, $attribute);
            }
        }
    }

    /**
     * Returns a value indicating whether the validator applies to the specified scenario.
     * A validator applies to a scenario as long as any of the following conditions is met:
     * <ul>
     * <li>the validator's "on" property is empty</li>
     * <li>the validator's "on" property contains the specified scenario</li>
     * </ul>
     * @param string $scenario scenario name
     * @return boolean whether the validator applies to the specified scenario.
     */
    public function applyTo(object $object, string $scenario): bool
    {
        if (isset($this->except[$scenario]))
            return false;
        return empty($this->on) || isset($this->on[$scenario]);
    }

    /**
     * Checks if the given value is empty.
     * A value is considered empty if it is null, an empty array, or the trimmed result is an empty string.
     * Note that this method is different from PHP empty(). It will return false when the value is 0.
     * @param mixed $value the value to be checked
     * @param boolean $trim whether to perform trimming before checking if the string is empty. Defaults to false.
     * @return boolean whether the value is empty
     */
    protected function isEmpty(mixed $value, bool $trim = false): bool
    {
        return $value === null || $value === array() || $value === '' || $trim && is_scalar($value) && trim($value) === '';
    }
}