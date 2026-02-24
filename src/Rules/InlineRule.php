<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * InlineRule represents a validator which is defined as a method in the object being validated.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class InlineRule extends AbstractRule
{
    /**
     * @var string the name of the validation method defined in the active record class
     */
    public string $method;
    /**
     * @var array additional parameters that are passed to the validation method
     */
    public array $params = [];

    /**
     * Validates the attribute of the object.
     * If there is any error, the error message is added to the object.
     * @param array|object $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute(array|object $object, string $attribute): void
    {
        $object->{$this->method}($attribute, $this->params, $this->validator);
    }
}
