<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

use DateTimeImmutable;

/**
 * TypeRule verifies if the attribute is of the type specified by {@link type}.
 *
 * The following data types are supported:
 * <ul>
 * <li><b>integer</b> A 32-bit signed integer data type.</li>
 * <li><b>float</b> A double-precision floating point number data type.</li>
 * <li><b>string</b> A string data type.</li>
 * <li><b>array</b> An array value. </li>
 * <li><b>date</b> A date data type.</li>
 * <li><b>time</b> A time data type.</li>
 * <li><b>datetime</b> A date and time data type.</li>
 * </ul>
 *
 * For <b>date</b> type, the property {@link dateFormat}
 * will be used to determine how to parse the date string. If the given date
 * value doesn't follow the format, the attribute is considered as invalid.
 *
 * Starting from version 1.1.7, we have a dedicated date validator {@link DateRule}.
 * Please consider using this validator to validate a date-typed value.
 *
 * When using the {@link message} property to define a custom error message, the message
 * may contain additional placeholders that will be replaced with the actual content. In addition
 * to the "{attribute}" placeholder, recognized by all validators (see {@link Validator}),
 * CTypeValidator allows for the following placeholders to be specified:
 * <ul>
 * <li>{type}: replaced with data type the attribute should be {@link type}.</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class TypeRule extends AbstractRule
{
    /**
     * @var string the data type that the attribute should be. Defaults to 'string'.
     * Valid values include 'string', 'integer', 'float', 'array', 'date', 'time' and 'datetime'.
     */
    public string $type = 'string';
    /**
     * @var string the format pattern that the date value should follow. Defaults to 'MM/dd/yyyy'.
     * Please see {@link CDateTimeParser} for details about how to specify a date format.
     * This property is effective only when {@link type} is 'date'.
     */
    public string $dateFormat = 'm/d/Y';
    /**
     * @var string the format pattern that the time value should follow. Defaults to 'hh:mm'.
     * Please see {@link CDateTimeParser} for details about how to specify a time format.
     * This property is effective only when {@link type} is 'time'.
     */
    public string $timeFormat = 'H:i';
    /**
     * @var string the format pattern that the datetime value should follow. Defaults to 'MM/dd/yyyy hh:mm'.
     * Please see {@link CDateTimeParser} for details about how to specify a datetime format.
     * This property is effective only when {@link type} is 'datetime'.
     */
    public string $datetimeFormat = 'm/d/Y H:i';
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;

    /**
     * @var boolean whether the actual PHP type of attribute value should be checked.
     * Defaults to false, meaning that correctly formatted strings are accepted for
     * integer and float validators.
     *
     * @since 1.1.13
     */
    public bool $strict = false;

    /**
     * Validates the attribute of the object.
     * If there is any error, the error message is added to the object.
     * @param object $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute(object $object, string $attribute): void
    {
        $value = $object->$attribute;
        if ($this->allowEmpty && $this->isEmpty($value))
            return;

        if (!$this->validateValue($value)) {
            $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} must be {type}.', [
                '{type}' => $this->type,
            ]);
        }
    }

    /**
     * Validates a static value.
     * Note that this method does not respect {@link allowEmpty} property.
     * This method is provided so that you can call it directly without going through the model validation rule mechanism.
     * @param mixed $value the value to be validated
     * @return boolean whether the value is valid
     * @since 1.1.13
     */
    protected function validateValue(mixed $value): bool
    {
        $type = $this->type === 'float' ? 'double' : $this->type;
        // строгий контроль
        if ($type === gettype($value)) {
            return true;
        }

        if ($this->strict || is_array($value) || is_object($value) || is_resource($value) || is_bool($value)) {
            return false;
        }

        return match ($type) {
            'integer' => (bool)preg_match('/^[-+]?[0-9]+$/', trim($value)),
            'double' => (bool)preg_match('/^[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/', trim($value)),
            'date' => $this->parseDateTime($value, $this->dateFormat) !== null,
            'time' => $this->parseDateTime($value, $this->timeFormat) !== null,
            'datetime' => $this->parseDateTime($value, $this->datetimeFormat) !== null,
            default => false,
        };
    }

    protected function parseDateTime(string $value, string $format): ?int
    {
        $dt = DateTimeImmutable::createFromFormat('!' . $format . '|', $value);
        if (($errors = DateTimeImmutable::getLastErrors()) && ($errors['error_count'] || $errors['warning_count'])) {
            return null;
        }
        return $dt ? (int)$dt->getTimestamp() : null;
    }
}