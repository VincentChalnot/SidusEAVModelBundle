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
use Sidus\EAVModelBundle\Attribute\EAVEmbedAttributeType;
use Sidus\EAVModelBundle\Attribute\EAVRelationAttributeType;
use Sidus\EAVModelBundle\Attribute\EmbedAttributeType;
use Sidus\EAVModelBundle\Attribute\IdentifierAttributeType;
use Sidus\EAVModelBundle\Attribute\RelationAttributeType;

class AttributeTypeTest extends TestCase
{
    public function testBasicAttributeType(): void
    {
        $type = new AttributeType('string', 'stringValue');

        $this->assertSame('string', $type->getCode());
        $this->assertSame('stringValue', $type->getDatabaseType());
        $this->assertFalse($type->isEmbedded());
        $this->assertFalse($type->isRelation());
    }

    public function testRelationAttributeType(): void
    {
        $type = new RelationAttributeType('data_selector', 'dataValue');

        $this->assertSame('data_selector', $type->getCode());
        $this->assertSame('dataValue', $type->getDatabaseType());
        $this->assertFalse($type->isEmbedded());
        $this->assertTrue($type->isRelation());
    }

    public function testEmbedAttributeType(): void
    {
        $type = new EmbedAttributeType('embed', 'dataValue');

        $this->assertSame('embed', $type->getCode());
        $this->assertSame('dataValue', $type->getDatabaseType());
        $this->assertTrue($type->isEmbedded());
        $this->assertTrue($type->isRelation());
    }

    public function testEAVRelationAttributeType(): void
    {
        $type = new EAVRelationAttributeType('eav_relation', 'dataValue');

        $this->assertSame('eav_relation', $type->getCode());
        $this->assertSame('dataValue', $type->getDatabaseType());
        $this->assertFalse($type->isEmbedded());
        $this->assertTrue($type->isRelation());
    }

    public function testEAVEmbedAttributeType(): void
    {
        $type = new EAVEmbedAttributeType('eav_embed', 'dataValue');

        $this->assertSame('eav_embed', $type->getCode());
        $this->assertSame('dataValue', $type->getDatabaseType());
        $this->assertTrue($type->isEmbedded());
        $this->assertTrue($type->isRelation());
    }

    public function testIdentifierAttributeType(): void
    {
        $type = new IdentifierAttributeType('string_identifier', 'stringValue');

        $this->assertSame('string_identifier', $type->getCode());
        $this->assertSame('stringValue', $type->getDatabaseType());
        $this->assertFalse($type->isEmbedded());
        $this->assertFalse($type->isRelation());
    }
}
