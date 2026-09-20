<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\RangeRule;
use Yii1x\Validator\Validator;

final class RangeRuleTest extends TestCase
{
    /* ---------- VALID VALUES ---------- */

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

    /* ---------- INVALID VALUES ---------- */

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

    /* ---------- CUSTOM MESSAGE ---------- */

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
        // value is in the list
        yield ['a', ['a', 'b']];
        yield [1, [1, 2, 3]];

        // loose comparisons
        yield ['1', [1, 2], false];   // '1' == 1
        yield [1, ['1', '2'], false]; // 1 == '1'

        // strict
        yield [1, [1, 2], true];

        // allowEmpty
        yield ['', [], false, false, true];
        yield [null, [], false, false, true];

        // not = true (value must NOT be in the list)
        yield ['x', ['a', 'b'], false, true];
    }

    public static function invalidProvider(): iterable
    {
        // value is missing
        yield ['z', ['a', 'b']];

        // strict mode, types do not match
        yield ['1', [1, 2], true];

        // not = true, but the value is in the list
        yield ['a', ['a', 'b'], true, true];
    }
}