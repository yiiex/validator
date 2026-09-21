<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\CompareRule;
use Yii1x\Validator\Validator;

final class CompareRuleTest extends TestCase
{
    /* ---------- VALID CASES ---------- */

    #[DataProvider('validProvider')]
    public function testValid(
        mixed  $value,
        mixed  $compareValue,
        string $operator = '=',
        bool   $strict   = false
    ): void {
        $obj = (object)[
            'password'        => $value,
            'password_repeat' => $compareValue,
            'age'             => $value,
            'min_age'         => $compareValue,
        ];
        $rule               = new CompareRule();
        $rule->operator     = $operator;
        $rule->strict       = $strict;
        $rule->allowEmpty   = false;
        $rule->attributes   = ['password', 'age'];
        $rule->compareValue = $compareValue;
        $rule->validator    = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('password'));
        $this->assertFalse($rule->validator->hasErrors('age'));
    }

    /* ---------- INVALID CASES ---------- */

    #[DataProvider('invalidProvider')]
    public function testInvalid(
        mixed  $value,
        mixed  $compareValue,
        string $operator = '=',
        bool   $strict   = false
    ): void {
        $obj = (object)[
            'password'        => $value,
            'password_repeat' => $compareValue,
        ];
        $rule               = new CompareRule();
        $rule->operator     = $operator;
        $rule->strict       = $strict;
        $rule->allowEmpty   = false;
        $rule->attributes   = ['password'];
        $rule->compareValue = $compareValue;
        $rule->validator    = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('password'));
    }

    /* ---------- allowEmpty ---------- */

    public function testAllowEmpty(): void
    {
        $obj = (object)['password' => ''];
        $rule               = new CompareRule();
        $rule->compareValue = '123';
        $rule->allowEmpty   = true;
        $rule->attributes   = ['password'];
        $rule->validator    = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('password'));
    }

    /* ---------- CUSTOM MESSAGE ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['password' => 'foo'];
        $rule               = new CompareRule();
        $rule->compareValue = 'bar';
        $rule->message      = 'Пароли должны совпадать';
        $rule->allowEmpty   = false;
        $rule->attributes   = ['password'];
        $rule->validator    = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('password');
        $this->assertContains('Пароли должны совпадать', $errors['password']);
    }

    /* ---------- COMPARISON WITH AN ATTRIBUTE (no compareValue) ---------- */

    public function testCompareWithDefaultRepeatAttribute(): void
    {
        $rule = new CompareRule();
        $rule->allowEmpty = false;
        $rule->attributes = ['password'];

        $valid = (object)['password' => 'secret', 'password_repeat' => 'secret'];
        $rule->validator = new Validator($valid);
        $rule->validate($valid);
        $this->assertFalse($rule->validator->hasErrors('password'));

        $invalid = (object)['password' => 'secret', 'password_repeat' => 'other'];
        $rule->validator = new Validator($invalid);
        $rule->validate($invalid);
        $this->assertTrue($rule->validator->hasErrors('password'));
    }

    public function testCompareAttributePlaceholderIsAttributeName(): void
    {
        $obj = (object)['password' => 'secret', 'password2' => 'other'];
        $rule = new CompareRule();
        $rule->compareAttribute = 'password2';
        $rule->message = '{attribute} must match {compareAttribute}';
        $rule->allowEmpty = false;
        $rule->attributes = ['password'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('password');
        $this->assertContains('password must match password2', $errors['password']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validProvider(): iterable
    {
        // basic equality
        yield ['secret', 'secret'];
        // loose types
        yield [1, '1', '=', false];
        // strict equality
        yield [1, 1, '=', true];
        // operators
        yield [5, 3, '>'];
        yield [3, 3, '>='];
        yield [1, 3, '<'];
        yield [3, 3, '<='];
        yield [2, 3, '!='];
    }

    public static function invalidProvider(): iterable
    {
        // basic inequality
        yield ['foo', 'bar'];
        // strict mode
        yield [1, '1', '=', true];
        // operators
        yield [2, 3, '>'];
        yield [2, 3, '>='];
        yield [4, 3, '<'];
        yield [4, 3, '<='];
        yield [3, 3, '!='];
    }
}