<?php

declare(strict_types=1);

/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2025 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Sidus\EAVModelBundle\Attribute\Attribute;
use Sidus\EAVModelBundle\Attribute\AttributeType;
use Sidus\EAVModelBundle\Attribute\AttributeTypeInterface;

class AttributeTest extends TestCase
{
    private AttributeTypeInterface $type;

    protected function setUp(): void
    {
        $this->type = new AttributeType('string', 'stringValue');
    }

    public function testCreateAttribute(): void
    {
        $attribute = new Attribute('title', $this->type);

        $this->assertSame('title', $attribute->getCode());
        $this->assertSame($this->type, $attribute->getType());
        $this->assertNull($attribute->getFamily());
        $this->assertFalse($attribute->isRequired());
        $this->assertFalse($attribute->isUnique());
        $this->assertFalse($attribute->isCollection());
    }

    public function testAttributeLabel(): void
    {
        $attribute = new Attribute('firstName', $this->type);

        // Without explicit label, should convert camelCase to readable
        $this->assertSame('First Name', $attribute->getLabel());

        // With explicit label
        $attribute->setLabel('Custom Label');
        $this->assertSame('Custom Label', $attribute->getLabel());
    }

    public function testAttributeOptions(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertSame([], $attribute->getOptions());
        $this->assertFalse($attribute->hasOption('foo'));
        $this->assertNull($attribute->getOption('foo'));
        $this->assertSame('default', $attribute->getOption('foo', 'default'));

        $attribute->addOption('foo', 'bar');
        $this->assertTrue($attribute->hasOption('foo'));
        $this->assertSame('bar', $attribute->getOption('foo'));
    }

    public function testAttributeRequired(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertFalse($attribute->isRequired());

        $attribute->setRequired(true);
        $this->assertTrue($attribute->isRequired());
    }

    public function testAttributeUnique(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertFalse($attribute->isUnique());

        $attribute->setUnique(true);
        $this->assertTrue($attribute->isUnique());
    }

    public function testAttributeCollection(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertFalse($attribute->isCollection());

        $attribute->setCollection(true);
        $this->assertTrue($attribute->isCollection());
    }

    public function testAttributeGroup(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertNull($attribute->getGroup());

        $attribute->setGroup('myGroup');
        $this->assertSame('myGroup', $attribute->getGroup());
    }

    public function testAttributeDefault(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertNull($attribute->getDefault());

        $attribute->setDefault('default value');
        $this->assertSame('default value', $attribute->getDefault());
    }

    public function testAttributeValidationRules(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertSame([], $attribute->getValidationRules());

        $rules = [['NotBlank' => null], ['Length' => ['max' => 255]]];
        $attribute->setValidationRules($rules);
        $this->assertSame($rules, $attribute->getValidationRules());
    }

    public function testAttributeContextMask(): void
    {
        $attribute = new Attribute('test', $this->type);

        $this->assertSame([], $attribute->getContextMask());

        $attribute->setContextMask(['locale', 'channel']);
        $this->assertSame(['locale', 'channel'], $attribute->getContextMask());
    }

    public function testContextMatching(): void
    {
        $attribute = new Attribute('test', $this->type);

        // Empty mask matches everything
        $this->assertTrue($attribute->isContextMatching([]));
        $this->assertTrue($attribute->isContextMatching(['locale' => 'en']));

        // With mask
        $attribute->setContextMask(['locale']);
        $this->assertTrue($attribute->isContextMatching(['locale' => 'en']));
        $this->assertTrue($attribute->isContextMatching(['locale' => 'en', 'extra' => 'value']));
        $this->assertFalse($attribute->isContextMatching([]));
        $this->assertFalse($attribute->isContextMatching(['other' => 'value']));
    }

    public function testMergeConfiguration(): void
    {
        $attribute = new Attribute('test', $this->type);

        $attribute->mergeConfiguration([
            'required' => true,
            'unique' => true,
            'group' => 'testGroup',
            'default' => 'myDefault',
        ]);

        $this->assertTrue($attribute->isRequired());
        $this->assertTrue($attribute->isUnique());
        $this->assertSame('testGroup', $attribute->getGroup());
        $this->assertSame('myDefault', $attribute->getDefault());
    }
}
