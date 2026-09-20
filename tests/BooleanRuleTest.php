<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\BooleanRule;
use Yii1x\Validator\Validator;

final class BooleanRuleTest extends TestCase
{
    /* ---------- VALID VALUES ---------- */

    #[DataProvider('validDataProvider')]
    public function testValid(
        mixed $value,
        mixed $trueValue,
        mixed $falseValue,
        bool  $strict,
        bool  $allowEmpty
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new BooleanRule();
        $rule->trueValue = $trueValue;
        $rule->falseValue = $falseValue;
        $rule->strict = $strict;
        $rule->allowEmpty = $allowEmpty;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    /* ---------- INVALID VALUES ---------- */

    #[DataProvider('invalidDataProvider')]
    public function testInvalid(
        mixed $value,
        mixed $trueValue,
        mixed $falseValue,
        bool  $strict
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new BooleanRule();
        $rule->trueValue = $trueValue;
        $rule->falseValue = $falseValue;
        $rule->strict = $strict;
        $rule->allowEmpty = false;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('attr'));
        $errors = $rule->validator->getErrors('attr');
        $this->assertStringContainsString('attr must be either', implode(' ', $errors['attr']));
    }

    /* ---------- CUSTOM MESSAGE ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['attr' => 'foo'];
        $rule = new BooleanRule();
        $rule->trueValue = 1;
        $rule->falseValue = 0;
        $rule->strict = false;
        $rule->allowEmpty = false;
        $rule->message = 'Поле {attribute} должно быть равно {true} или {false}';
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertContains('Поле attr должно быть равно 1 или 0', $errors['attr']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validDataProvider(): iterable
    {
        // default values
        yield ['1', '1', '0', false, false];
        yield ['0', '1', '0', false, false];

        // different types
        yield [true, true, false, true, false];
        yield [false, true, false, true, false];

        // allowEmpty = true
        yield ['', '1', '0', false, true];
        yield [null, '1', '0', false, true];

        // loose comparisons
        yield [1, '1', '0', false, false];
        yield [0, '1', '0', false, false];

        // custom true/false
        yield ['yes', 'yes', 'no', false, false];
        yield ['no', 'yes', 'no', false, false];
    }

    public static function invalidDataProvider(): iterable
    {
        yield ['2', '1', '0', false];
        yield ['yes', '1', '0', false];
        yield [2, 1, 0, true];
        yield ['1', 1, 0, true]; // strict comparison of string and number
        yield [null, '1', '0', false]; // allowEmpty = false
    }
}