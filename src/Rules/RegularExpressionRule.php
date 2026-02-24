<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * CRegularExpressionValidator validates that the attribute value matches to the specified {@link pattern regular expression}.
 * You may invert the validation logic with help of the {@link not} property (available since 1.1.5).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class RegularExpressionRule extends AbstractRule
{
    /**
     * @var string|null the regular expression to be matched with
     */
    public ?string $pattern = null;
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;
    /**
     * @var boolean whether to invert the validation logic. Defaults to false. If set to true,
     * the regular expression defined via {@link pattern} should NOT match the attribute value.
     * @since 1.1.5
     **/
    public bool $not = false;

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
        if ($this->pattern === null)
            throw new \InvalidArgumentException('The "pattern" property must be specified with a valid regular expression.');
        // reason of array checking explained here: https://github.com/yiisoft/yii/issues/1955
        if (is_array($value) ||
            (!$this->not && !preg_match($this->pattern, $value)) ||
            ($this->not && preg_match($this->pattern, $value))) {
            $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} is invalid.');
        }
    }
}