<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\BooleanRule;
use Yii1x\Validator\Validator;

final class BooleanRuleTest extends TestCase
{
    /* ---------- ВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

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

    /* ---------- НЕВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

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

    /* ---------- ПРОВЕРКА КАСТОМНОГО СООБЩЕНИЯ ---------- */

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
        // Стандартные значения по умолчанию
        yield ['1', '1', '0', false, false];
        yield ['0', '1', '0', false, false];

        // Разные типы
        yield [true, true, false, true, false];
        yield [false, true, false, true, false];

        // allowEmpty = true
        yield ['', '1', '0', false, true];
        yield [null, '1', '0', false, true];

        // Нестрогие сравнения
        yield [1, '1', '0', false, false];
        yield [0, '1', '0', false, false];

        // Кастомные true/false
        yield ['yes', 'yes', 'no', false, false];
        yield ['no', 'yes', 'no', false, false];
    }

    public static function invalidDataProvider(): iterable
    {
        yield ['2', '1', '0', false];
        yield ['yes', '1', '0', false];
        yield [2, 1, 0, true];
        yield ['1', 1, 0, true]; // строгое сравнение строки и числа
        yield [null, '1', '0', false]; // allowEmpty = false
    }
}