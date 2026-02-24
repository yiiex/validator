<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * CBooleanValidator validates that the attribute value is either {@link trueValue}  or {@link falseValue}.
 *
 * When using the {@link message} property to define a custom error message, the message
 * may contain additional placeholders that will be replaced with the actual content. In addition
 * to the "{attribute}" placeholder, recognized by all validators (see {@link Validator}),
 * CBooleanValidator allows for the following placeholders to be specified:
 * <ul>
 * <li>{true}: replaced with value representing the true status {@link trueValue}.</li>
 * <li>{false}: replaced with value representing the false status {@link falseValue}.</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 */
class BooleanRule extends AbstractRule
{
    /**
     * @var mixed the value representing true status. Defaults to '1'.
     */
    public mixed $trueValue = '1';
    /**
     * @var mixed the value representing false status. Defaults to '0'.
     */
    public mixed $falseValue = '0';
    /**
     * @var boolean whether the comparison to {@link trueValue} and {@link falseValue} is strict.
     * When this is true, the attribute value and type must both match those of {@link trueValue} or {@link falseValue}.
     * Defaults to false, meaning only the value needs to be matched.
     */
    public bool $strict = false;
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;

    /**
     * Validates the attribute of the object.
     * If there is any error, the error message is added to the object.
     * @param object $object $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute(object $object, string $attribute): void
    {
        $value = $object->$attribute;
        if ($this->allowEmpty && $this->isEmpty($value))
            return;

        if (!$this->validateValue($value)) {
            $message = $this->message !== null ? $this->message : '{attribute} must be either {true} or {false}.';
            $this->validator->addError($attribute, $message, [
                '{true}' => $this->trueValue,
                '{false}' => $this->falseValue,
            ]);
        }
    }

    /**
     * Validates a static value to see if it is a valid boolean.
     * This method is provided so that you can call it directly without going
     * through the model validation rule mechanism.
     *
     * Note that this method does not respect the {@link allowEmpty} property.
     *
     * @param mixed $value the value to be validated
     * @return boolean whether the value is a valid boolean
     * @since 1.1.17
     */
    public function validateValue($value)
    {
        if ($this->strict)
            return $value === $this->trueValue || $value === $this->falseValue;
        else
            return $value == $this->trueValue || $value == $this->falseValue;
    }

}
