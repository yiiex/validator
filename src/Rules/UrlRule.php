<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;
/**
 * UrlRule validates that the attribute value is a valid http or https URL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class UrlRule extends AbstractRule
{
    /**
     * @var string the regular expression used to validate the attribute value.
     * Since version 1.1.7 the pattern may contain a {schemes} token that will be replaced
     * by a regular expression which represents the {@see validSchemes}.
     */
    public string $pattern = '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';
    /**
     * @var array list of URI schemes which should be considered valid. By default, http and https
     * are considered to be valid schemes.
     * @since 1.1.7
     **/
    public array $validSchemes = ['http', 'https'];
    /**
     * @var string|null the default URI scheme. If the input doesn't contain the scheme part, the default
     * scheme will be prepended to it (thus changing the input). Defaults to null, meaning a URL must
     * contain the scheme part.
     * @since 1.1.7
     **/
    public ?string $defaultScheme = null;
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;
    /**
     * @var boolean whether validation process should care about IDN (internationalized domain names). Default
     * value is false which means that validation of URLs containing IDN will always fail.
     * @since 1.1.13
     */
    public bool $validateIDN = false;

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
        if (($value = $this->validateValue($value)) !== false) {
            $object->$attribute = $value;
        } else {
            $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} is not a valid URL.');
        }
    }

    protected function validateValue(mixed $value): string|false
    {
        if (!is_string($value) || strlen($value) >= 2000) {
            return false;
        }

        if ($this->defaultScheme !== null && !str_contains($value, '://')) {
            $value = $this->defaultScheme . '://' . $value;
        }

        // IDN-кодирование / декодирование
        if ($this->validateIDN) {
            $value = $this->handleIDN($value);
        }

        // проверка схемы
        $schemes = implode('|', $this->validSchemes);
        $pattern = sprintf('#^(%s)://([a-z0-9-]+\.)*[a-z0-9-]+(/.*)?$#i', $schemes);
        return preg_match($pattern, $value) ? $value : false;
    }

    protected function handleIDN(string $url): string
    {
        if (!str_contains($url, '://')) {
            return $url;
        }

        [$scheme, $rest] = explode('://', $url, 2);
        [$host, $path]   = explode('/', $rest, 2) + ['', ''];

        $host = \idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
        return $scheme . '://' . $host . ($path !== '' ? '/' . $path : '');
    }
}
