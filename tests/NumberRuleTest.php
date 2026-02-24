<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\NumberRule;
use Yii1x\Validator\Validator;

final class NumberRuleTest extends TestCase
{
    /* ---------- ВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

    #[DataProvider('validProvider')]
    public function testValid(
        mixed          $value,
        bool           $integerOnly = false,
        int|float|null $min = null,
        int|float|null $max = null
    ): void
    {
        $obj = (object)['num' => $value];
        $rule = new NumberRule();
        $rule->integerOnly = $integerOnly;
        $rule->min = $min;
        $rule->max = $max;
        $rule->allowEmpty = false;
        $rule->attributes = ['num'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('num'));
    }

    /* ---------- НЕВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

    #[DataProvider('invalidProvider')]
    public function testInvalid(
        mixed          $value,
        bool           $integerOnly = false,
        int|float|null $min = null,
        int|float|null $max = null
    ): void
    {
        $obj = (object)['num' => $value];
        $rule = new NumberRule();
        $rule->integerOnly = $integerOnly;
        $rule->min = $min;
        $rule->max = $max;
        $rule->allowEmpty = false;
        $rule->attributes = ['num'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('num'));
    }

    /* ---------- allowEmpty ---------- */

    public function testAllowEmpty(): void
    {
        $obj = (object)['num' => ''];
        $rule = new NumberRule();
        $rule->allowEmpty = true;
        $rule->attributes = ['num'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('num'));
    }

    /* ---------- КАСТОМНЫЕ СООБЩЕНИЯ ---------- */

    public function testCustomMessages(): void
    {
        $obj = (object)['num' => 5];
        $rule = new NumberRule();
        $rule->min = 10;
        $rule->max = 20;
        $rule->tooSmall = 'Слишком мало: {min}';
        $rule->tooBig = 'Слишком много: {max}';
        $rule->allowEmpty = false;
        $rule->attributes = ['num'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('num');
        $this->assertContains('Слишком мало: 10', $errors['num']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validProvider(): iterable
    {
        // обычные числа
        yield [42];
        yield [3.14];
        yield [-7];
        yield [' 123 '];
        yield ['+7.5'];

        // только целые
        yield [42, true];
        yield ['  -99  ', true];

        // диапазон
        yield [5, false, 1, 10];
        yield [7, true, 5, 10];
    }

    public static function invalidProvider(): iterable
    {
        // не число
        yield ['abc'];
        yield ['12a'];

        // не целое
        yield [3.14, true];

        // за пределами
        yield [0, false, 5, 10];
        yield [15, false, 5, 10];
        yield [4.5, false, 5, 10];
    }
}