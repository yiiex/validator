<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace Yii1x\Validator\Rules;

/**
 * EmailRule validates that the attribute value is a valid email address.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.0
 */
class EmailRule extends AbstractRule
{
    /**
     * @var string the regular expression used to validate the attribute value.
     * @see http://www.regular-expressions.info/email.html
     */
    public string $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
    /**
     * @var string the regular expression used to validate email addresses with the name part.
     * This property is used only when {@link allowName} is true.
     * @see allowName
     */
    public string $fullPattern = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
    /**
     * @var boolean whether to allow name in the email address (e.g. "Qiang Xue <qiang.xue@gmail.com>"). Defaults to false.
     * @see fullPattern
     */
    public bool $allowName = false;
    /**
     * @var boolean whether to check the MX record for the email address.
     * Defaults to false. To enable it, you need to make sure the PHP function 'checkdnsrr'
     * exists in your PHP installation.
     * Please note that this check may fail due to temporary problems even if email is deliverable.
     */
    public bool $checkMX = false;
    /**
     * @var boolean whether to check port 25 for the email address.
     * Defaults to false. To enable it, ensure that the PHP functions 'dns_get_record' and
     * 'fsockopen' are available in your PHP installation.
     * Please note that this check may fail due to temporary problems even if email is deliverable.
     */
    public bool $checkPort = false;
    /**
     * @var null|int timeout to use when attempting to open connection to port in checkMxPorts. If null (default)
     * use default_socket_timeout value from php.ini. If not null the timeout is set in seconds.
     * @since 1.1.19
     */
    public int|null $timeout = null;
    /**
     * @var boolean whether the attribute value can be null or empty. Defaults to true,
     * meaning that if the attribute is empty, it is considered valid.
     */
    public bool $allowEmpty = true;
    /**
     * @var boolean whether validation process should care about IDN (internationalized domain names). Default
     * value is false which means that validation of emails containing IDN will always fail.
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

        if (!$this->validateValue($value)) {
            $this->validator->addError($attribute, $this->message !== null ? $this->message : '{attribute} is not a valid email address.');
        }
    }

    /**
     * Validates a static value to see if it is a valid email.
     * Note that this method does not respect {@see $allowEmpty}.
     */
    public function validateValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // length-limit to mitigate DoS
        if (strlen($value) > 254) {
            return false;
        }

        // handle IDN
        if ($this->validateIDN) {
            $value = $this->encodeIDN($value);
        }

        $pattern = $this->allowName ? $this->fullPattern : $this->pattern;

        if (!preg_match($pattern, $value)) {
            return false;
        }

        $domain = substr($value, strrpos($value, '@') + 1);
        if ($this->allowName) {
            $domain = rtrim($domain, '>');
        }

        // MX-record check
        if ($this->checkMX && function_exists('checkdnsrr')) {
            if (!checkdnsrr($domain, 'MX')) {
                return false;
            }
        }

        // port 25 check
        if ($this->checkPort && function_exists('dns_get_record') && function_exists('fsockopen')) {
            if (!$this->checkMxPorts($domain)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tries to open port 25 on any MX host for the given domain.
     */
    private function checkMxPorts(string $domain): bool
    {
        /** @var list<array{target:string,pri:int}> $records */
        $records = dns_get_record($domain, DNS_MX);
        if ($records === false || $records === []) {
            return false;
        }

        usort($records, static fn(array $a, array $b): int => $a['pri'] <=> $b['pri']);

        $timeout = $this->timeout ?? (int)ini_get('default_socket_timeout');

        foreach ($records as $record) {
            $handle = @fsockopen($record['target'], 25, $errno, $errstr, $timeout);
            if ($handle !== false) {
                fclose($handle);
                return true;
            }
        }

        return false;
    }

    /**
     * Converts an UTF-8 e-mail address with possible IDN to ASCII (punycode).
     */
    private function encodeIDN(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $asciiDomain = idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);

        return $local . '@' . ($asciiDomain ?: $domain);
    }
}
