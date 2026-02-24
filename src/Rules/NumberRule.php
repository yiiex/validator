<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * NumberRule validates that the attribute value is a number.
 *
 * In addition to the {@link message} property for setting a custom error message,
 * CNumberValidator has a couple custom error messages you can set that correspond to different
 * validation scenarios. To specify a custom message when the numeric value is too big,
 * you may use the {@link tooBig} property. Similarly with {@link tooSmall}.
 * The messages may contain additional placeholders that will be replaced
 * with the actual content. In addition to the "{attribute}" placeholder, recognized by all
 * validators (see {@link Validator}), CNumberValidator allows for the following placeholders
 * to be specified:
 * <ul>
 * <li>{min}: when using {@link tooSmall}, replaced with the lower limit of the number {@link min}.</li>
 * <li>{max}: when using {@link tooBig}, replaced with the upper limit of the number {@link max}.</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class NumberRule extends AbstractRule
{
    /**
     * @var boolean whether the attribute value can only be an integer. Defaults to false.
     */
    public bool $integerOnly = false;
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;
    /**
     * @var int|float|null upper limit of the number. Defaults to null, meaning no upper limit.
     */
    public int|float|null $max = null;
    /**
     * @var int|float|null lower limit of the number. Defaults to null, meaning no lower limit.
     */
    public int|float|null $min = null;
    /**
     * @var string|null user-defined error message used when the value is too big.
     */
    public ?string $tooBig = null;
    /**
     * @var string|null user-defined error message used when the value is too small.
     */
    public ?string $tooSmall = null;
    /**
     * @var string the regular expression for matching integers.
     * @since 1.1.7
     */
    public string $integerPattern = '/^\s*[+-]?\d+\s*$/';
    /**
     * @var string the regular expression for matching numbers.
     * @since 1.1.7
     */
    public string $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';


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
        if (!is_numeric($value)) {
            // https://github.com/yiisoft/yii/issues/1955
            // https://github.com/yiisoft/yii/issues/1669
            $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} must be a number.');
            return;
        }
        if ($this->integerOnly) {
            if (!preg_match($this->integerPattern, "$value")) {
                $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} must be an integer.');
            }
        } else {
            if (!preg_match($this->numberPattern, "$value")) {
                $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} must be a number.');
            }
        }
        if ($this->min !== null && $value < $this->min) {
            $this->validator->addError($attribute, $this->tooSmall !== null ? $this->tooSmall : '{attribute} is too small (minimum is {min}).', [
                '{min}' => $this->min,
            ]);
        }
        if ($this->max !== null && $value > $this->max) {
            $this->validator->addError($attribute, $this->tooBig !== null ? $this->tooBig : '{attribute} is too big (maximum is {max}).', [
                '{max}' => $this->max,
            ]);
        }
    }
}
