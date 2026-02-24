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
 * DateRule verifies if the attribute represents a date, time or datetime.
 *
 * By setting the {@link format} property, one can specify what format the date value
 * must be in. If the given date value doesn't follow the format, the attribute is considered as invalid.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDateValidator.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.validators
 * @since 1.1.7
 */
class DateRule extends AbstractRule
{
    /**
     * @var mixed the format pattern that the date value should follow.
     * This can be either a string or an array representing multiple formats.
     * Defaults to m/d/Y. Please see {@link DateTimeImmutable} for details
     * about how to specify a date format.
     */
    public string|array $format = 'm/d/Y';
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;
    /**
     * @var null|string the name of the attribute to receive the parsing result.
     * When this property is not null and the validation is successful, the named attribute will
     * receive the parsing result.
     */
    public ?string $timestampAttribute = null;

    /**
     * Validates the attribute of the object.
     * If there is any error, the error message is added to the object.
     * @param object $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute(object $object, string $attribute): void
    {
        $value = $object->$attribute;
        if ($this->allowEmpty && $this->isEmpty($value)) {
            return;
        }
        if (!is_array($value) && $value !== null) {
            $formats = (array)$this->format;
            foreach ($formats as $fmt) {
                $dt = DateTimeImmutable::createFromFormat('!' . $fmt . '|', (string)$value);
                if ($dt !== false && !DateTimeImmutable::getLastErrors()) {
                    if ($this->timestampAttribute !== null) {
                        $object->{$this->timestampAttribute} = $dt->getTimestamp();
                    }
                    return;
                }
            }
        }

        $this->validator->addError($attribute, $this->message ?? 'The format of {attribute} is invalid.');
    }
}

