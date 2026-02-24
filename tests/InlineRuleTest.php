<?php
declare(strict_types=1);

namespace Yii1x\Validator\Tests;

use PHPUnit\Framework\TestCase;
use Yii1x\Validator\Rules\InlineRule;
use Yii1x\Validator\Validator;

final class InlineRuleTest extends TestCase
{
    public function testInlineMethodCalled(): void
    {
        $model = new class {
            public string $name = 'abc';

            public function checkName(string $attribute, array $params, Validator $validator): void
            {
                if (strlen($this->$attribute) < $params['min']) {
                    $validator->addError($attribute, 'Name must be at least 5 chars.');
                }
            }
        };

        $rule = new InlineRule();
        $rule->method = 'checkName';
        $rule->params = ['min' => 5];
        $rule->attributes = ['name'];
        $rule->validator = new Validator($model);

        $rule->validate($model);

        $this->assertTrue($rule->validator->hasErrors('name'));
        $this->assertContains('Name must be at least 5 chars.', $rule->validator->getErrors('name')['name']);
    }

    public function testInlineMethodPasses(): void
    {
        $model = new class {
            public string $name = 'valid';

            public function checkName(string $attribute, array $params, Validator $validator): void
            {
                // no-op
            }
        };

        $rule = new InlineRule();
        $rule->method = 'checkName';
        $rule->attributes = ['name'];
        $rule->validator = new Validator($model);

        $rule->validate($model);

        $this->assertFalse($rule->validator->hasErrors('name'));
    }
}