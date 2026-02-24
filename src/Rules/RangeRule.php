<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * RangeRule validates that the attribute value is among the list (specified via {@link range}).
 * You may invert the validation logic with help of the {@link not} property (available since 1.1.5).
 * For example,
 * <pre>
 * class QuestionForm extends FormModel
 * {
 *     public function rules()
 *     {
 *         return array(
 *             array('text, tag', 'required'),
 *             array('text, 'type', 'type' => 'string'),
 *             array('tag', 'in', 'range' => array('php', 'mysql', 'jquery')),
 *         );
 *     }
 * }
 * </pre>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class RangeRule extends AbstractRule
{
    /**
     * @var array list of valid values that the attribute value should be among
     */
    public array $range;
    /**
     * @var boolean whether the comparison is strict (both type and value must be the same)
     */
    public bool $strict = false;
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;
    /**
     * @var boolean whether to invert the validation logic. Defaults to false. If set to true,
     * the attribute value should NOT be among the list of values defined via {@link range}.
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
        $result = false;
        if ($this->strict) {
            $result = in_array($value, $this->range, true);
        } else {
            foreach ($this->range as $r) {
                $result = $r === '' || $value === '' ? $r === $value : $r == $value;
                if ($result)
                    break;
            }
        }
        if (!$this->not && !$result) {
            $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} is not in the list.');
        } elseif ($this->not && $result) {
            $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} is in the list.');
        }
    }

}
