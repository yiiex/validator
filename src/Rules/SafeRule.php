<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * CSafeValidator marks the associated attributes to be safe for massive assignments.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.1
 */
class SafeRule extends AbstractRule
{
    /**
     * Validates the attribute of the object.
     * If there is any error, the error message is added to the object.
     * @param object $object $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute(object $object, string $attribute): void
    {
    }
}

