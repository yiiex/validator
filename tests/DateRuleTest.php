<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\DateRule;
use Yii1x\Validator\Validator;

final class DateRuleTest extends TestCase
{
    /* ---------- VALID STRINGS ---------- */

    #[DataProvider('validDataProvider')]
    public function testValid(
        string $value,
        string|array $format,
        ?string $timestampAttribute
    ): void {
        $obj = (object)['attr' => $value];
        $rule = new DateRule();
        $rule->format = $format;
        $rule->timestampAttribute = $timestampAttribute;
        $rule->allowEmpty = false;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
        if ($timestampAttribute) {
            $this->assertIsInt($obj->{$timestampAttribute});
        }
    }

    /* ---------- INVALID STRINGS ---------- */

    #[DataProvider('invalidDataProvider')]
    public function testInvalid(
        string $value,
        string|array $format
    ): void {
        $obj = (object)['attr' => $value];
        $rule = new DateRule();
        $rule->format = $format;
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
        $rule = new DateRule();
        $rule->allowEmpty = true;
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('attr'));
    }

    /* ---------- CUSTOM MESSAGE ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['attr' => 'bad-date'];
        $rule = new DateRule();
        $rule->format = 'Y-m-d';
        $rule->allowEmpty = false;
        $rule->message = 'Неверный формат даты для {attribute}';
        $rule->attributes = ['attr'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('attr');
        $this->assertContains('Неверный формат даты для attr', $errors['attr']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validDataProvider(): iterable
    {
        // single format
        yield ['12/31/2024', 'm/d/Y', null];
        yield ['31/12/2024', 'd/m/Y', null];
        yield ['2024-12-31', 'Y-m-d', null];

        // multiple formats
        yield ['31-12-2024', ['Y-m-d', 'd-m-Y'], null];

        // saves timestamp
        yield ['2024-12-31', 'Y-m-d', 'ts'];
    }

    public static function invalidDataProvider(): iterable
    {
        // invalid day / month
        yield ['2024-13-45', 'Y-m-d'];
        yield ['31/31/2024', 'm/d/Y'];

        // invalid format
        yield ['not-a-date', 'Y-m-d'];
        yield ['2024-12-31 25:00', 'Y-m-d H:i'];
    }
}