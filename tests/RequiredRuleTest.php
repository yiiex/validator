<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\RequiredRule;
use Yii1x\Validator\Validator;

final class RequiredRuleTest extends TestCase
{
    #[DataProvider('validDataProvider')]
    public function testValid(
        mixed $value,
        mixed $requiredValue,
        bool  $strict,
        bool  $trim
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new RequiredRule();
        $rule->requiredValue = $requiredValue;
        $rule->strict = $strict;
        $rule->trim = $trim;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    #[DataProvider('invalidDataProvider')]
    public function testInvalid(
        mixed  $value,
        mixed  $requiredValue,
        bool   $strict,
        bool   $trim,
        string $expectedMessage
    ): void
    {
        $obj = (object)['attr' => $value];
        $rule = new RequiredRule();
        $rule->requiredValue = $requiredValue;
        $rule->strict = $strict;
        $rule->trim = $trim;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertArrayHasKey('attr', $errors);
        $this->assertContains($expectedMessage, $errors['attr']);
    }

    public function testCustomMessage(): void
    {
        $obj = (object)['attr' => ''];
        $rule = new RequiredRule();
        $rule->message = 'Custom required message';
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertArrayHasKey('attr', $errors);
        $this->assertContains($rule->message, $errors['attr']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validDataProvider(): iterable
    {
        yield ['abc', null, false, true];
        yield [1, null, false, true];
        yield [' 123 ', null, false, false];
        yield ['foo', 'foo', false, true];
        yield [42, 42, false, true];
        yield [42, 42, true, true];
    }

    public static function invalidDataProvider(): iterable
    {
        yield ['', null, false, true, 'attr cannot be blank.'];
        yield ['  ', null, false, true, 'attr cannot be blank.'];
        yield [null, null, false, true, 'attr cannot be blank.'];
        yield ['bar', 'foo', false, true, 'attr must be foo.'];
        yield [42, '42', true, true, 'attr must be 42.'];
    }
}