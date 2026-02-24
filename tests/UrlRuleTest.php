<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\UrlRule;
use Yii1x\Validator\Validator;

final class UrlRuleTest extends TestCase
{
    /* ---------- ВАЛИДНЫЕ URL ---------- */

    #[DataProvider('validUrlProvider')]
    public function testValid(
        string  $url,
        array   $validSchemes,
        ?string $defaultScheme = null
    ): void
    {
        $obj = (object)['link' => $url];
        $rule = new UrlRule();
        $rule->validSchemes = $validSchemes;
        $rule->defaultScheme = $defaultScheme;
        $rule->allowEmpty = false;
        $rule->attributes = ['link'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('link'));
    }

    /* ---------- НЕВАЛИДНЫЕ URL ---------- */

    #[DataProvider('invalidUrlProvider')]
    public function testInvalid(string $url): void
    {
        $obj = (object)['link' => $url];
        $rule = new UrlRule();
        $rule->allowEmpty = false;
        $rule->attributes = ['link'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertTrue($rule->validator->hasErrors('link'));
    }

    /* ---------- defaultScheme ---------- */

    #[DataProvider('defaultSchemeProvider')]
    public function testDefaultScheme(
        string  $input,
        ?string $defaultScheme,
        string  $expectedAfter
    ): void
    {
        $obj = (object)['link' => $input];
        $rule = new UrlRule();
        $rule->defaultScheme = $defaultScheme;
        $rule->validSchemes = ['http', 'https', 'ftp'];
        $rule->allowEmpty = false;
        $rule->attributes = ['link'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertSame($expectedAfter, $obj->link);
    }

    /* ---------- validateIDN ---------- */

    public function testValidateIDN(): void
    {
        $obj = (object)['link' => 'http://пример.рф'];
        $rule = new UrlRule();
        $rule->validateIDN = true;
        $rule->allowEmpty = false;
        $rule->attributes = ['link'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('link'));
    }

    /* ---------- allowEmpty ---------- */

    public function testAllowEmpty(): void
    {
        $obj = (object)['link' => ''];
        $rule = new UrlRule();
        $rule->allowEmpty = true;
        $rule->attributes = ['link'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $this->assertFalse($rule->validator->hasErrors('link'));
    }

    /* ---------- КАСТОМНОЕ СООБЩЕНИЕ ---------- */

    public function testCustomMessage(): void
    {
        $obj = (object)['link' => 'not-an-url'];
        $rule = new UrlRule();
        $rule->allowEmpty = false;
        $rule->message = 'Неверный URL для {attribute}';
        $rule->attributes = ['link'];
        $rule->validator = new Validator($obj);

        $rule->validate($obj);

        $errors = $rule->validator->getErrors('link');
        $this->assertContains('Неверный URL для link', $errors['link']);
    }

    /* ---------- DATA PROVIDERS ---------- */

    public static function validUrlProvider(): iterable
    {
        yield ['https://example.com', ['http', 'https']];
        yield ['http://sub.domain.co.uk', ['http', 'https']];
        yield ['https://example.com/path?query=1', ['http', 'https']];
        yield ['ftp://files.example.com', ['ftp']];
        yield ['example.com', ['http'], 'http'];
        yield ['http://example-.com', ['http', 'https']];
    }

    public static function invalidUrlProvider(): iterable
    {
        yield ['ftp://example.com']; // ftp не разрешён по умолчанию
        yield ['example.com']; // без схемы и без defaultScheme
        yield ['http://'];
        yield ['http://example .com']; // пробелы в хосте
    }

    public static function defaultSchemeProvider(): iterable
    {
        yield ['example.com', 'https', 'https://example.com'];
        yield ['example.com', 'ftp', 'ftp://example.com'];
        yield ['https://example.com', null, 'https://example.com'];
    }
}