<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\TypeRule;
use Yii1x\Validator\Validator;

final class TypeRuleTest extends TestCase
{
    /* ---------- ВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

    #[DataProvider('validDataProvider')]
    public function testValid(
        mixed  $value,
        string $type,
        bool   $allowEmpty,
        bool   $strict
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new TypeRule();
        $rule->type = $type;
        $rule->allowEmpty = $allowEmpty;
        $rule->strict = $strict;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    /* ---------- НЕВАЛИДНЫЕ ЗНАЧЕНИЯ ---------- */

    #[DataProvider('invalidDataProvider')]
    public function testInvalid(
        mixed  $value,
        string $type,
        string $expectedMessage
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new TypeRule();
        $rule->type = $type;
        $rule->allowEmpty = false;   // проверяем всё
        $rule->strict = false;   // по умолчанию
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertArrayHasKey('attr', $errors);
        $this->assertContains($expectedMessage, $errors['attr']);
    }

    /* ---------- STRICT-РЕЖИМ ---------- */

    #[DataProvider('strictDataProvider')]
    public function testStrict(
        mixed  $value,
        string $type,
        bool   $expectedValid
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new TypeRule();
        $rule->type = $type;
        $rule->strict = true;
        $rule->allowEmpty = false;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame(!$expectedValid, $rule->validator->hasErrors('attr'));
    }

    /* ---------- КАСТОМНОЕ СООБЩЕНИЕ ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['attr' => 'abc'];
        $rule = new TypeRule();
        $rule->type = 'integer';
        $rule->message = 'Поле {attribute} должно быть {type}';
        $rule->allowEmpty = false;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertContains('Поле attr должно быть integer', $errors['attr']);
    }

    /* ---------- ДАТЫ / ВРЕМЯ / DATETIME ---------- */

    #[DataProvider('dateDataProvider')]
    public function testDateParsing(
        string $format,
        string $type,
        string $value,
        bool   $expectedValid
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new TypeRule();
        $rule->type = $type;
        $rule->allowEmpty = false;
        $rule->dateFormat = $type === 'date' ? $format : 'm/d/Y';
        $rule->timeFormat = $type === 'time' ? $format : 'H:i';
        $rule->datetimeFormat = $type === 'datetime' ? $format : 'm/d/Y H:i';
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame(!$expectedValid, $rule->validator->hasErrors('attr'));
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validDataProvider(): iterable
    {
        // allowEmpty = true
        yield ['', 'string', true, false];

        // string
        yield ['hello', 'string', false, false];
        yield ['123', 'string', false, false];

        // integer (не strict)
        yield [42, 'integer', false, false];
        yield ['42', 'integer', false, false];

        // float (не strict)
        yield [3.14, 'float', false, false];
        yield ['3.14', 'float', false, false];

        // array
        yield [[1, 2], 'array', false, false];

        // date/time/datetime
        yield ['12/31/2024', 'date', false, false];
        yield ['23:59', 'time', false, false];
        yield ['12/31/2024 23:59', 'datetime', false, false];
    }

    public static function invalidDataProvider(): iterable
    {
        yield ['abc', 'integer', 'attr must be integer.'];
        yield ['12.34', 'integer', 'attr must be integer.'];
        yield ['abc', 'float', 'attr must be float.'];
        yield ['2024-13-45', 'date', 'attr must be date.'];
        yield ['25:00', 'time', 'attr must be time.'];
        yield ['2024-12-31 25:00', 'datetime', 'attr must be datetime.'];
        yield [new \stdClass(), 'string', 'attr must be string.'];
    }

    public static function strictDataProvider(): iterable
    {
        yield [42, 'integer', true];      // int == int
        yield ['42', 'integer', false];   // string != int
        yield [3.14, 'float', true];      // float == float
        yield ['3.14', 'float', false];   // string != float
        yield [[1, 2], 'array', true];    // array == array
    }

    public static function dateDataProvider(): iterable
    {
        // date
        yield ['m/d/Y', 'date', '12/31/2024', true];
        yield ['m/d/Y', 'date', '31/12/2024', false];

        // time
        yield ['H:i', 'time', '23:59', true];
        yield ['H:i', 'time', '25:00', false];

        // datetime
        yield ['m/d/Y H:i', 'datetime', '12/31/2024 23:59', true];
        yield ['m/d/Y H:i', 'datetime', '12/31/2024 25:00', false];
    }
}