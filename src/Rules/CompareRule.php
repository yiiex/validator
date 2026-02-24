<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * CCompareValidator compares the specified attribute value with another value and validates if they are equal.
 *
 * The value being compared with can be another attribute value
 * (specified via {@link compareAttribute}) or a constant (specified via
 * {@link compareValue}. When both are specified, the latter takes
 * precedence. If neither is specified, the attribute will be compared
 * with another attribute whose name is by appending "_repeat" to the source
 * attribute name.
 *
 * The comparison can be either {@link strict} or not.
 *
 * CCompareValidator supports different comparison operators.
 * Previously, it only compares to see if two values are equal or not.
 *
 * When using the {@link message} property to define a custom error message, the message
 * may contain additional placeholders that will be replaced with the actual content. In addition
 * to the "{attribute}" placeholder, recognized by all validators (see {@link Validator}),
 * CCompareValidator allows for the following placeholders to be specified:
 * <ul>
 * <li>{compareValue}: replaced with the constant value being compared with ({@link compareValue}).</li>
 * <li>{compareAttribute}: replaced with the label of the attribute being compared with ({@link compareAttribute}).</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class CompareRule extends AbstractRule
{
    /**
     * @var string|null the name of the attribute to be compared with
     */
    public ?string $compareAttribute = null;
    /**
     * @var string|null the constant value to be compared with
     */
    public mixed $compareValue = null;
    /**
     * @var boolean whether the comparison is strict (both value and type must be the same.)
     * Defaults to false.
     */
    public bool $strict = false;
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to false.
     * If this is true, it means the attribute is considered valid when it is empty.
     */
    public bool $allowEmpty = false;
    /**
     * @var string the operator for comparison. Defaults to '='.
     * The followings are valid operators:
     * <ul>
     * <li>'=' or '==': validates to see if the two values are equal. If {@link strict} is true, the comparison
     * will be done in strict mode (i.e. checking value type as well).</li>
     * <li>'!=': validates to see if the two values are NOT equal. If {@link strict} is true, the comparison
     * will be done in strict mode (i.e. checking value type as well).</li>
     * <li>'>': validates to see if the value being validated is greater than the value being compared with.</li>
     * <li>'>=': validates to see if the value being validated is greater than or equal to the value being compared with.</li>
     * <li>'<': validates to see if the value being validated is less than the value being compared with.</li>
     * <li>'<=': validates to see if the value being validated is less than or equal to the value being compared with.</li>
     * </ul>
     */
    public string $operator = '=';

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
        if ($this->compareValue !== null)
            $compareTo = $compareValue = $this->compareValue;
        else {
            $compareAttribute = $this->compareAttribute === null ? $attribute . '_repeat' : $this->compareAttribute;
            $compareValue = $object->$compareAttribute;
            $compareTo = $object->getAttributeLabel($compareAttribute);
        }

        switch ($this->operator) {
            case '=':
            case '==':
                if (($this->strict && $value !== $compareValue) || (!$this->strict && $value != $compareValue))
                    $message = $this->message !== null ? $this->message : '{attribute} must be repeated exactly.';
                break;
            case '!=':
                if (($this->strict && $value === $compareValue) || (!$this->strict && $value == $compareValue))
                    $message = $this->message !== null ? $this->message : '{attribute} must not be equal to "{compareValue}".';
                break;
            case '>':
                if ($value <= $compareValue)
                    $message = $this->message !== null ? $this->message : '{attribute} must be greater than "{compareValue}".';
                break;
            case '>=':
                if ($value < $compareValue)
                    $message = $this->message !== null ? $this->message : '{attribute} must be greater than or equal to "{compareValue}".';
                break;
            case '<':
                if ($value >= $compareValue)
                    $message = $this->message !== null ? $this->message : '{attribute} must be less than "{compareValue}".';
                break;
            case '<=':
                if ($value > $compareValue)
                    $message = $this->message !== null ? $this->message : '{attribute} must be less than or equal to "{compareValue}".';
                break;
            default:
                throw new \InvalidArgumentException('Invalid operator "' . $this->operator . '".');
        }
        if (!empty($message))
            $this->validator->addError($attribute, $message, ['{compareAttribute}' => $compareTo, '{compareValue}' => $compareValue]);
    }

}
