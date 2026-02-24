<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * UnsafeRule marks the associated attributes to be unsafe so that they cannot be massively assigned.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class UnsafeRule extends AbstractRule
{
    /**
     * @var boolean whether attributes listed with this validator should be considered safe for massive assignment.
     * Defaults to false.
     * @since 1.1.4
     */
    public bool $safe = false;

    /**
     * Validates the attribute of the object.
     * This validator does not do any validation as it is meant
     * to only mark attributes as unsafe.
     * @param object $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute(object $object, string $attribute):void
    {
    }
}

