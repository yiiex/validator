<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Validator;

final class ValidatorTest extends TestCase
{
    /* ---------- БАЗОВАЯ ВАЛИДАЦИЯ ---------- */

    public function testValidatePasses(): void
    {
        $obj = (object)['name' => 'Alice', 'age' => 30];
        $validator = new Validator($obj, [
            ['name', 'required'],
            ['age', 'type', 'type' => 'integer'],
        ]);

        $this->assertTrue($validator->validate());
    }

    public function testValidateFails(): void
    {
        $obj = (object)['name' => '', 'age' => 'nan'];
        $validator = new Validator($obj, [
            ['name', 'required'],
            ['age', 'type', 'type' => 'integer'],
        ]);

        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->hasErrors('name'));
        $this->assertTrue($validator->hasErrors('age'));
    }

    /* ---------- СЦЕНАРИИ ---------- */

    public function testScenarioOn(): void
    {
        $obj = (object)['name' => ''];
        $validator = new Validator($obj, [
            ['name', 'required', 'on' => 'insert'],
        ]);

        $this->assertFalse($validator->validate('insert'));
        $this->assertTrue($validator->validate('update')); // правило не применяется
    }

    public function testScenarioExcept(): void
    {
        $obj = (object)['name' => ''];
        $validator = new Validator($obj, [
            ['name', 'required', 'except' => 'update'],
        ]);

        $this->assertTrue($validator->validate('update')); // исключено
        $this->assertFalse($validator->validate('insert'));
    }

    /* ---------- СОЗДАНИЕ ПРАВИЛ ЧЕРЕЗ АЛИАС ---------- */

    public function testCreateValidatorByAlias(): void
    {
        $obj = (object)['email' => 'not-an-email'];
        $validator = new Validator($obj, [
            ['email', 'email'],
        ]);

        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->hasErrors('email'));
    }

    /* ---------- INLINE-МЕТОД ---------- */

    public function testInlineRule(): void
    {
        $model = new class {
            public string $password = '123';
            public string $password_repeat = '456';

            public function checkPassword(string $attr, array $params, Validator $validator): void
            {
                if ($this->password !== $this->password_repeat) {
                    $validator->addError($attr, 'Passwords do not match.');
                }
            }
        };
        $validator = new Validator($model, [
            ['password', 'checkPassword'],
        ]);

        $this->assertFalse($validator->validate());
    }

    /* ---------- ОШИБКА НЕВАЛИДНОГО ПРАВИЛА ---------- */

    public function testInvalidRuleThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The rule "unknown" does not exist');

        $obj = new \stdClass();
        $validator = new Validator($obj, [['field', 'unknown']]);

        // триггерим создание валидаторов
        $validator->validate();
    }

    /* ---------- НЕСКОЛЬКО АТРИБУТОВ ---------- */

    public function testMultipleAttributes(): void
    {
        $obj = (object)['a' => 'foo', 'b' => ''];
        $validator = new Validator($obj, [
            ['a, b', 'required'],
        ]);

        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->hasErrors('b'));
        $this->assertFalse($validator->hasErrors('a'));
    }

    /* ---------- ГЕТТЕРЫ ---------- */

    public function testGetRequiredAttributes(): void
    {
        $obj = new \stdClass();
        $validator = new Validator($obj, [
            ['name', 'required', 'on' => 'create'],
            ['email', 'required'],
        ]);

        $this->assertSame(['name', 'email'], $validator->getRequiredAttributes('create'));
    }

    public function testGetSafeAttributes(): void
    {
        $obj = new \stdClass();
        $validator = new Validator($obj, [
            ['name', 'safe'],
            ['password', 'unsafe'],
        ]);

        $this->assertSame(['name'], $validator->getSafeAttributes());
    }

    /* ---------- ДАТА-ПРОВАЙДЕРЫ ---------- */

    #[DataProvider('attributeListProvider')]
    public function testParseAttributeList(string $input, array $expected): void
    {
        // небольшой хелпер через рефлексию
        $validator = new Validator(new \stdClass());
        $m = new \ReflectionMethod($validator, 'createValidator');
        $m->setAccessible(true);
        $rule = $m->invoke($validator, 'required', new \stdClass(), $input, []);
        $this->assertSame($expected, $rule->attributes);
    }

    public static function attributeListProvider(): iterable
    {
        yield ['name', ['name']];
        yield [' name ', ['name']];
        yield ['name, email', ['name', 'email']];
        yield ['name,email', ['name', 'email']];
    }
}