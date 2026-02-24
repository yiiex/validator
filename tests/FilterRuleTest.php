<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\FilterRule;
use Yii1x\Validator\Validator;

final class FilterRuleTest extends TestCase
{
    /* ---------- ПРОВЕРКА ВСТРОЕННЫХ ФУНКЦИЙ ---------- */

    #[DataProvider('builtinFilterProvider')]
    public function testBuiltinFilter(
        string     $input,
        callable   $filter,
        string|int $expected
    ): void
    {
        $obj = (object)['attr' => $input];
        $rule = new FilterRule();
        $rule->filter = $filter;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame($expected, $obj->attr);
        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    /* ---------- ПРОВЕРКА АНОНИМНОЙ ФУНКЦИИ ---------- */

    public function testAnonymousFilter(): void
    {
        $obj = (object)['attr' => 'hello'];
        $rule = new FilterRule();
        $rule->filter = fn($v) => strtoupper($v);
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame('HELLO', $obj->attr);
    }

    /* ---------- ПРОВЕРКА НЕСКОЛЬКИХ АТРИБУТОВ ---------- */

    public function testMultipleAttributes(): void
    {
        $obj = (object)[
            'a' => '  foo  ',
            'b' => '  bar  ',
        ];
        $rule = new FilterRule();
        $rule->filter = 'trim';
        $rule->attributes = ['a', 'b'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame('foo', $obj->a);
        $this->assertSame('bar', $obj->b);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function builtinFilterProvider(): iterable
    {
        yield ['  text  ', 'trim', 'text'];
        yield ['UPPER', 'strtolower', 'upper'];
        yield ['upper', 'strtoupper', 'UPPER'];
        yield ['12text', 'intval', 12];
    }
}