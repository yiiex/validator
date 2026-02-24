<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\StringRule;
use Yii1x\Validator\Validator;

final class StringRuleTest extends TestCase
{
    /* ---------- ПРОВЕРКИ ВАЛИДНЫХ ЗНАЧЕНИЙ ---------- */

    #[DataProvider('validDataProvider')]
    public function testValid(
        mixed            $value,
        ?int             $min,
        ?int             $max,
        ?int             $exact,
        bool             $allowEmpty,
        string|bool|null $encoding
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new StringRule();
        $rule->min = $min;
        $rule->max = $max;
        $rule->is = $exact;
        $rule->allowEmpty = $allowEmpty;
        $rule->encoding = $encoding;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    /* ---------- ПРОВЕРКИ НЕВАЛИДНЫХ ЗНАЧЕНИЙ ---------- */

    #[DataProvider('invalidDataProvider')]
    public function testInvalid(
        mixed  $value,
        ?int   $min,
        ?int   $max,
        ?int   $exact,
        string $expectedMessage
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new StringRule();
        $rule->min = $min;
        $rule->max = $max;
        $rule->is = $exact;
        $rule->allowEmpty = false;        // принудительно проверяем
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertArrayHasKey('attr', $errors);
        $this->assertContains($expectedMessage, $errors['attr']);
    }

    /* ---------- КАСТОМНЫЕ СООБЩЕНИЯ ---------- */

    public function testCustomMessages(): void
    {
        $obj = (object)['attr' => 'hi'];

        $rule = new StringRule();
        $rule->min = 5;
        $rule->tooShort = 'Слишком коротко ({min})';
        $rule->allowEmpty = false;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertContains('Слишком коротко (5)', $errors['attr']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validDataProvider(): iterable
    {
        // allowEmpty = true
        yield ['', null, null, null, true, null];
        yield [null, 1, 10, null, true, null];

        // обычные строки
        yield ['abc', 1, 10, null, false, null];
        yield ['абв', 1, 10, null, false, 'UTF-8']; // mb_strlen

        // exact length
        yield ['12345', null, null, 5, false, null];

        // границы
        yield ['123', 3, 5, null, false, null];
    }

    public static function invalidDataProvider(): iterable
    {
        // too short
        yield ['a', 2, null, null, 'attr is too short (minimum is 2 characters).'];

        // too long
        yield ['abcdef', 1, 5, null, 'attr is too long (maximum is 5 characters).'];

        // exact length
        yield ['123', null, null, 4, 'attr is of the wrong length (should be 4 characters).'];

        // массив недопустим
        yield [[], null, null, null, 'attr is invalid.'];
    }
}