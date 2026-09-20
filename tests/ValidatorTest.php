<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\AbstractRule;
use Yii1x\Validator\Rules\RequiredRule;
use Yii1x\Validator\Validator;

final class ValidatorTest extends TestCase
{
    /* ---------- BASIC VALIDATION ---------- */

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

    /* ---------- SCENARIOS ---------- */

    public function testScenarioOn(): void
    {
        $obj = (object)['name' => ''];
        $validator = new Validator($obj, [
            ['name', 'required', 'on' => 'insert'],
        ]);

        $this->assertFalse($validator->validate('insert'));
        $this->assertTrue($validator->validate('update')); // rule does not apply
    }

    public function testScenarioExcept(): void
    {
        $obj = (object)['name' => ''];
        $validator = new Validator($obj, [
            ['name', 'required', 'except' => 'update'],
        ]);

        $this->assertTrue($validator->validate('update')); // excluded
        $this->assertFalse($validator->validate('insert'));
    }

    /* ---------- CREATING RULES BY ALIAS ---------- */

    public function testCreateValidatorByAlias(): void
    {
        $obj = (object)['email' => 'not-an-email'];
        $validator = new Validator($obj, [
            ['email', 'email'],
        ]);

        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->hasErrors('email'));
    }

    /* ---------- INLINE METHOD ---------- */

    public function testInlineRule(): void
    {
        $model = new class {
            public string $password = '123';
            public string $password_repeat = '456';

            public function checkPassword(string $attribute, array $params, Validator $validator): void
            {
                if ($this->password !== $this->password_repeat) {
                    $validator->addError($attribute, 'Passwords do not match.');
                }
            }
        };
        $validator = new Validator($model, [
            ['password', 'checkPassword'],
        ]);

        $this->assertFalse($validator->validate());
    }

    /* ---------- INVALID RULE ---------- */

    public function testInvalidRuleThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The rule "unknown" does not exist');

        $obj = new \stdClass();
        $validator = new Validator($obj, [['field', 'unknown']]);

        // trigger validator creation
        $validator->validate();
    }

    /* ---------- MULTIPLE ATTRIBUTES ---------- */

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

    /* ---------- GETTERS ---------- */

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

    /* ---------- CUSTOM RULE CLASS ---------- */

    public function testCustomRuleClass(): void
    {
        $customRule = new class extends \Yii1x\Validator\Rules\AbstractRule {
            protected function validateAttribute(object $object, string $attribute): void
            {
                if ($object->$attribute !== 'valid') {
                    $this->validator->addError($attribute, $this->message ?? 'Value must be "valid"');
                }
            }
        };

        $obj = (object)[
            'field1' => 'valid',
            'field2' => 'invalid',
        ];

        $validator = new Validator($obj, [
            ['field1', $customRule::class],
            ['field2', $customRule::class, 'message' => 'Custom error message'],
        ]);

        $this->assertFalse($validator->validate());
        $this->assertFalse($validator->hasErrors('field1'));
        $this->assertTrue($validator->hasErrors('field2'));

        // the custom message is used
        $errors = $validator->getErrors('field2');
        $this->assertStringContainsString('Custom error message', implode(' ', $errors['field2']));
    }

    /* ---------- WHEN ---------- */

    public function testWhenSkipsAndAppliesRule(): void
    {
        $rules = ['name', 'required', 'when' => fn($model) => $model->type === 'b'];

        $validator = new Validator((object)['type' => 'a', 'name' => ''], [$rules]);
        $this->assertTrue($validator->validate());

        $validator = new Validator((object)['type' => 'b', 'name' => ''], [$rules]);
        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->hasErrors('name'));
    }

    public function testWhenInlineRule(): void
    {
        $model = new class {
            public array $received = [];
            public bool $check = true;

            public function checkName(string $attribute, array $params, Validator $validator): void
            {
                $this->received = $params;
                $validator->addError($attribute, 'Name is invalid.');
            }
        };

        $rules = [['name', 'checkName', 'when' => fn($model) => $model->check, 'foo' => 'bar']];

        $validator = new Validator($model, $rules);
        $this->assertFalse($validator->validate());
        $this->assertTrue($validator->hasErrors('name'));
        $this->assertArrayNotHasKey('when', $model->received);
        $this->assertSame('bar', $model->received['foo']);

        $model->check = false;
        $validator = new Validator($model, $rules);
        $this->assertTrue($validator->validate());
    }

    public function testWhenMustBeCallable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "when" property must be a callable');

        $validator = new Validator((object)['name' => ''], [
            ['name', 'required', 'when' => 'definitely-not-a-function-xyz'],
        ]);

        $validator->validate();
    }

    public function testScenarioFilteringAndWhenOrder(): void
    {
        $whenCalls = 0;
        $rules = [
            ['name', 'required', 'on' => 'insert'],
            ['email', 'required'],
            ['email', 'required', 'on' => 'insert', 'when' => function ($model) use (&$whenCalls) {
                $whenCalls++;
                return false;
            }],
        ];
        $obj = (object)['name' => '', 'email' => ''];

        // null and '' are the default (empty) scenario
        foreach ([null, ''] as $scenario) {
            $whenCalls = 0;
            $validator = new Validator($obj, $rules);
            $validator->validate($scenario);

            $this->assertFalse($validator->hasErrors('name'));   // "on" rule is skipped
            $this->assertTrue($validator->hasErrors('email'));   // rule without "on" runs
            $this->assertSame(0, $whenCalls);                    // "when" is not reached
        }

        $whenCalls = 0;
        $validator = new Validator($obj, $rules);
        $validator->validate('insert');

        $this->assertTrue($validator->hasErrors('name'));
        $this->assertSame(1, $whenCalls);                        // "when" runs once, last
    }

    /* ---------- EXTENDING: RULE FACTORY HOOKS ---------- */

    public function testCreateInstanceHooks(): void
    {
        $model = new class {
            public string $name = '';

            public function checkName(string $attribute, array $params, Validator $validator): void
            {
            }
        };

        $rule = $this->createMock(AbstractRule::class);
        $rule->method('applyTo')->willReturn(true);
        $rule->expects($this->once())->method('validate');

        $inline = $this->createMock(AbstractRule::class);
        $inline->method('applyTo')->willReturn(true);
        $inline->expects($this->once())->method('validate');

        $validator = new class($model, [
            ['name', 'required'],
            ['name', 'checkName'],
        ], $rule, $inline) extends Validator {
            public array $madeRules = [];
            public array $madeInline = [];

            public function __construct(
                object $object,
                array $rules,
                private AbstractRule $rule,
                private AbstractRule $inline,
            ) {
                parent::__construct($object, $rules);
            }

            protected function createRuleInstance(string $class, object $object): AbstractRule
            {
                $this->madeRules[] = $class;
                return $this->rule;
            }

            protected function createInlineRuleInstance(string $method, object $object, array $params): AbstractRule
            {
                $this->madeInline[] = $method;
                return $this->inline;
            }
        };

        $validator->validate();

        $this->assertSame([RequiredRule::class], $validator->madeRules);
        $this->assertSame(['checkName'], $validator->madeInline);
    }

    /* ---------- DATA PROVIDERS ---------- */

    #[DataProvider('attributeListProvider')]
    public function testParseAttributeList(string $input, array $expected): void
    {
        // small helper via reflection
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
