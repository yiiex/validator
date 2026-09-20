<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\RegularExpressionRule;
use Yii1x\Validator\Validator;

final class RegularExpressionRuleTest extends TestCase
{
    /* ---------- VALID VALUES ---------- */

    #[DataProvider('validProvider')]
    public function testValid(
        string $value,
        string $pattern,
        bool   $not = false
    ): void
    {
        $obj = (object)['code' => $value];
        $rule = new RegularExpressionRule();
        $rule->pattern = $pattern;
        $rule->not = $not;
        $rule->allowEmpty = false;
        $rule->attributes = ['code'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('code'));
    }

    /* ---------- INVALID VALUES ---------- */

    #[DataProvider('invalidProvider')]
    public function testInvalid(
        string $value,
        string $pattern,
        bool   $not = false
    ): void
    {
        $obj = (object)['code' => $value];
        $rule = new RegularExpressionRule();
        $rule->pattern = $pattern;
        $rule->not = $not;
        $rule->allowEmpty = false;
        $rule->attributes = ['code'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('code'));
    }

    /* ---------- allowEmpty ---------- */

    public function testAllowEmpty(): void
    {
        $obj = (object)['code' => ''];
        $rule = new RegularExpressionRule();
        $rule->pattern = '/^\d+$/';
        $rule->allowEmpty = true;
        $rule->attributes = ['code'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('code'));
    }

    /* ---------- MISSING pattern ---------- */

    public function testMissingPatternThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "pattern" property must be specified');

        $obj = (object)['code' => 'abc'];
        $rule = new RegularExpressionRule();
        $rule->attributes = ['code'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);
    }

    /* ---------- CUSTOM MESSAGE ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['code' => 'xyz'];
        $rule = new RegularExpressionRule();
        $rule->pattern = '/^\d+$/';
        $rule->allowEmpty = false;
        $rule->message = $message = 'Код должен состоять только из цифр';
        $rule->attributes = ['code'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('code');
        $this->assertContains($message, $errors['code']);
    }

    /* ---------- ARRAYS ARE REJECTED ---------- */

    public function testArrayIsInvalid(): void
    {
        $obj = (object)['code' => ['not', 'string']];
        $rule = new RegularExpressionRule();
        $rule->pattern = '/^[a-z]+$/';
        $rule->allowEmpty = false;
        $rule->attributes = ['code'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('code'));
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validProvider(): iterable
    {
        // regular match
        yield ['123', '/^\d+$/'];
        yield ['abc', '/^[a-z]+$/'];

        // not = true (value must NOT match)
        yield ['abc', '/^\d+$/', true];
    }

    public static function invalidProvider(): iterable
    {
        // no match
        yield ['abc', '/^\d+$/'];
        yield ['123', '/^[a-z]+$/'];

        // not = true, but it matched
        yield ['123', '/^\d+$/', true];
    }
}