<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\DefaultValueRule;
use Yii1x\Validator\Validator;

final class DefaultValueRuleTest extends TestCase
{
    /* ---------- setOnEmpty = true (default) ---------- */

    #[DataProvider('setOnEmptyTrueProvider')]
    public function testSetOnEmptyTrue(
        mixed $initialValue,
        mixed $defaultValue,
        mixed $expectedAfter
    ): void {
        $obj  = (object)['attr' => $initialValue];
        $rule = new DefaultValueRule();
        $rule->value        = $defaultValue;
        $rule->setOnEmpty   = true;
        $rule->attributes   = ['attr'];
        $rule->validator    = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame($expectedAfter, $obj->attr);
        $this->assertFalse($rule->validator->hasErrors('attr')); // no errors
    }

    /* ---------- setOnEmpty = false ---------- */

    #[DataProvider('setOnEmptyFalseProvider')]
    public function testSetOnEmptyFalse(
        mixed $initialValue,
        mixed $defaultValue,
        mixed $expectedAfter
    ): void {
        $obj  = (object)['attr' => $initialValue];
        $rule = new DefaultValueRule();
        $rule->value        = $defaultValue;
        $rule->setOnEmpty   = false;
        $rule->attributes   = ['attr'];
        $rule->validator    = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame($expectedAfter, $obj->attr);
    }

    /* ---------- MULTIPLE ATTRIBUTES ---------- */

    public function testMultipleAttributes(): void
    {
        $obj = (object)[
            'a' => null,
            'b' => '',
            'c' => 'already set',
        ];
        $rule = new DefaultValueRule();
        $rule->value      = 'DEFAULT';
        $rule->attributes = ['a', 'b', 'c'];
        $rule->validator  = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame('DEFAULT', $obj->a);
        $this->assertSame('DEFAULT', $obj->b);
        $this->assertSame('already set', $obj->c); // not overwritten
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function setOnEmptyTrueProvider(): iterable
    {
        // null or empty string -> default is set
        yield [null, 42, 42];
        yield ['', 'default', 'default'];
        yield [0, 'default', 0];      // 0 is not considered empty
        yield [false, 'default', false];
        yield ['not empty', 'default', 'not empty']; // left as is
    }

    public static function setOnEmptyFalseProvider(): iterable
    {
        // setOnEmpty = false -> always overwrite
        yield [null, 42, 42];
        yield ['', 'default', 'default'];
        yield ['already set', 'new', 'new'];
        yield [0, 'zero', 'zero'];
    }
}