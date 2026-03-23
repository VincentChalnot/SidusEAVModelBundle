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
use Sidus\EAVModelBundle\Attribute\AttributeType;
use Sidus\EAVModelBundle\Attribute\AttributeTypeRegistry;
use Sidus\EAVModelBundle\Exception\MissingAttributeTypeException;

class AttributeTypeRegistryTest extends TestCase
{
    private AttributeTypeRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new AttributeTypeRegistry();
    }

    public function testAddAndGetType(): void
    {
        $type = new AttributeType('string', 'stringValue');
        $this->registry->addType($type);

        $this->assertTrue($this->registry->hasType('string'));
        $this->assertSame($type, $this->registry->getType('string'));
    }

    public function testGetNonExistingType(): void
    {
        $this->expectException(MissingAttributeTypeException::class);
        $this->registry->getType('nonexistent');
    }

    public function testHasType(): void
    {
        $this->assertFalse($this->registry->hasType('string'));

        $type = new AttributeType('string', 'stringValue');
        $this->registry->addType($type);

        $this->assertTrue($this->registry->hasType('string'));
    }

    public function testGetTypes(): void
    {
        $type1 = new AttributeType('string', 'stringValue');
        $type2 = new AttributeType('integer', 'integerValue');

        $this->registry->addType($type1);
        $this->registry->addType($type2);

        $types = $this->registry->getTypes();

        $this->assertCount(2, $types);
        $this->assertSame($type1, $types['string']);
        $this->assertSame($type2, $types['integer']);
    }

    public function testGetTypeCodes(): void
    {
        $this->registry->addType(new AttributeType('string', 'stringValue'));
        $this->registry->addType(new AttributeType('integer', 'integerValue'));

        $codes = $this->registry->getTypeCodes();

        $this->assertSame(['string', 'integer'], $codes);
    }
}
