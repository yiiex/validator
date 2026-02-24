<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\RangeRule;
use Yii1x\Validator\Validator;

final class RangeRuleTest extends TestCase
{
    /* ---------- ВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

    #[DataProvider('validProvider')]
    public function testValid(
        mixed $value,
        array $range,
        bool  $strict = false,
        bool  $not = false,
        bool  $allowEmpty = false
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new RangeRule();
        $rule->range = $range;
        $rule->strict = $strict;
        $rule->not = $not;
        $rule->allowEmpty = $allowEmpty;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    /* ---------- НЕВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

    #[DataProvider('invalidProvider')]
    public function testInvalid(
        mixed $value,
        array $range,
        bool  $strict = false,
        bool  $not = false
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new RangeRule();
        $rule->range = $range;
        $rule->strict = $strict;
        $rule->not = $not;
        $rule->allowEmpty = false;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('attr'));
    }

    /* ---------- allowEmpty ---------- */

    public function testAllowEmpty(): void
    {
        $obj = (object)['attr' => ''];
        $rule = new RangeRule();
        $rule->range = ['a', 'b'];
        $rule->allowEmpty = true;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    /* ---------- КАСТОМНОЕ СООБЩЕНИЕ ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['attr' => 'z'];
        $rule = new RangeRule();
        $rule->range = ['a', 'b'];
        $rule->allowEmpty = false;
        $rule->message = $message = 'Выберите одно из доступных значений';
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertContains($message, $errors['attr']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validProvider(): iterable
    {
        // обычное попадание в список
        yield ['a', ['a', 'b']];
        yield [1, [1, 2, 3]];

        // не-строгие сравнения
        yield ['1', [1, 2], false];   // '1' == 1
        yield [1, ['1', '2'], false]; // 1 == '1'

        // strict
        yield [1, [1, 2], true];

        // allowEmpty
        yield ['', [], false, false, true];
        yield [null, [], false, false, true];

        // not = true (значение НЕ должно быть в списке)
        yield ['x', ['a', 'b'], false, true];
    }

    public static function invalidProvider(): iterable
    {
        // значение отсутствует
        yield ['z', ['a', 'b']];

        // строгий режим, типы не совпадают
        yield ['1', [1, 2], true];

        // not = true, но значение есть в списке
        yield ['a', ['a', 'b'], true, true];
    }
}