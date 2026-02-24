<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\EmailRule;
use Yii1x\Validator\Validator;

final class EmailRuleTest extends TestCase
{
    /* ---------- ВАЛИДНЫЕ АДРЕСА ---------- */

    #[DataProvider('validEmailProvider')]
    public function testValid(
        string $email,
        bool   $allowName = false,
        bool   $validateIDN = false
    ): void
    {
        $obj = (object)['email' => $email];
        $rule = new EmailRule();
        $rule->allowName = $allowName;
        $rule->validateIDN = $validateIDN;
        $rule->allowEmpty = false;
        $rule->attributes = ['email'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('email'));
    }

    /* ---------- НЕВАЛИДНЫЕ АДРЕСА ---------- */

    #[DataProvider('invalidEmailProvider')]
    public function testInvalid(string $email): void
    {
        $obj = (object)['email' => $email];
        $rule = new EmailRule();
        $rule->allowEmpty = false;
        $rule->attributes = ['email'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('email'));
    }

    /* ---------- allowName ---------- */

    #[DataProvider('allowNameProvider')]
    public function testAllowName(string $email, bool $allowName, bool $expectedValid): void
    {
        $obj = (object)['email' => $email];
        $rule = new EmailRule();
        $rule->allowName = $allowName;   // ← берём из провайдера
        $rule->allowEmpty = false;
        $rule->attributes = ['email'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame($expectedValid, !$rule->validator->hasErrors('email'));
    }

    /* ---------- allowEmpty ---------- */

    public function testAllowEmpty(): void
    {
        $obj = (object)['email' => ''];
        $rule = new EmailRule();
        $rule->allowEmpty = true;
        $rule->attributes = ['email'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('email'));
    }

    /* ---------- IDN ---------- */

    public function testIDN(): void
    {
        $obj = (object)['email' => 'ivan@пример.рф'];
        $rule = new EmailRule();
        $rule->validateIDN = true;
        $rule->allowEmpty = false;
        $rule->attributes = ['email'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('email'));
    }

    /* ---------- КАСТОМНОЕ СООБЩЕНИЕ ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['email' => 'not-an-email'];
        $rule = new EmailRule();
        $rule->allowEmpty = false;
        $rule->message = 'Неверный email: {attribute}';
        $rule->attributes = ['email'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('email');
        $this->assertContains('Неверный email: email', $errors['email']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validEmailProvider(): iterable
    {
        yield ['user@example.com'];
        yield ['test.email+tag@sub.domain.org'];
        yield ['123@test.co.uk'];
    }

    public static function invalidEmailProvider(): iterable
    {
        yield ['plainstring'];
        yield ['@missing-local.org'];
        yield ['user@.com'];
        yield ['user@com.'];
        yield ['user space@test.com'];
    }

    public static function allowNameProvider(): iterable
    {
        yield ['Qiang Xue <qiang.xue@gmail.com>', true, true];   // allowName=true → валидно
        yield ['Qiang Xue <qiang.xue@gmail.com>', false, false]; // allowName=false → невалидно
    }
}