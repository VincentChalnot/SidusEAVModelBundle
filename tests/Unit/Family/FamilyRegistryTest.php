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

namespace Sidus\EAVModelBundle\Tests\Unit\Family;

use PHPUnit\Framework\TestCase;
use Sidus\EAVModelBundle\Exception\MissingFamilyException;
use Sidus\EAVModelBundle\Family\FamilyInterface;
use Sidus\EAVModelBundle\Family\FamilyRegistry;

class FamilyRegistryTest extends TestCase
{
    private FamilyRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new FamilyRegistry();
    }

    public function testAddAndGetFamily(): void
    {
        $family = $this->createMock(FamilyInterface::class);
        $family->method('getCode')->willReturn('Post');

        $this->registry->addFamily($family);

        $this->assertTrue($this->registry->hasFamily('Post'));
        $this->assertSame($family, $this->registry->getFamily('Post'));
    }

    public function testGetNonExistingFamily(): void
    {
        $this->expectException(MissingFamilyException::class);
        $this->registry->getFamily('nonexistent');
    }

    public function testHasFamily(): void
    {
        $this->assertFalse($this->registry->hasFamily('Post'));

        $family = $this->createMock(FamilyInterface::class);
        $family->method('getCode')->willReturn('Post');
        $this->registry->addFamily($family);

        $this->assertTrue($this->registry->hasFamily('Post'));
    }

    public function testGetFamilies(): void
    {
        $family1 = $this->createMock(FamilyInterface::class);
        $family1->method('getCode')->willReturn('Post');

        $family2 = $this->createMock(FamilyInterface::class);
        $family2->method('getCode')->willReturn('Author');

        $this->registry->addFamily($family1);
        $this->registry->addFamily($family2);

        $families = $this->registry->getFamilies();

        $this->assertCount(2, $families);
        $this->assertSame($family1, $families['Post']);
        $this->assertSame($family2, $families['Author']);
    }

    public function testGetFamilyCodes(): void
    {
        $family1 = $this->createMock(FamilyInterface::class);
        $family1->method('getCode')->willReturn('Post');

        $family2 = $this->createMock(FamilyInterface::class);
        $family2->method('getCode')->willReturn('Author');

        $this->registry->addFamily($family1);
        $this->registry->addFamily($family2);

        $codes = $this->registry->getFamilyCodes();

        $this->assertSame(['Post', 'Author'], $codes);
    }

    public function testGetRootFamilies(): void
    {
        $rootFamily = $this->createMock(FamilyInterface::class);
        $rootFamily->method('getCode')->willReturn('Root');
        $rootFamily->method('isInstantiable')->willReturn(true);
        $rootFamily->method('getParent')->willReturn(null);

        $childFamily = $this->createMock(FamilyInterface::class);
        $childFamily->method('getCode')->willReturn('Child');
        $childFamily->method('isInstantiable')->willReturn(true);
        $childFamily->method('getParent')->willReturn($rootFamily);

        $abstractFamily = $this->createMock(FamilyInterface::class);
        $abstractFamily->method('getCode')->willReturn('Abstract');
        $abstractFamily->method('isInstantiable')->willReturn(false);
        $abstractFamily->method('getParent')->willReturn(null);

        $this->registry->addFamily($rootFamily);
        $this->registry->addFamily($childFamily);
        $this->registry->addFamily($abstractFamily);

        $rootFamilies = $this->registry->getRootFamilies();

        $this->assertCount(1, $rootFamilies);
        $this->assertSame($rootFamily, $rootFamilies['Root']);
    }

    public function testGetInstantiableFamilies(): void
    {
        $instantiable = $this->createMock(FamilyInterface::class);
        $instantiable->method('getCode')->willReturn('Instantiable');
        $instantiable->method('isInstantiable')->willReturn(true);

        $abstract = $this->createMock(FamilyInterface::class);
        $abstract->method('getCode')->willReturn('Abstract');
        $abstract->method('isInstantiable')->willReturn(false);

        $this->registry->addFamily($instantiable);
        $this->registry->addFamily($abstract);

        $families = $this->registry->getInstantiableFamilies();

        $this->assertCount(1, $families);
        $this->assertSame($instantiable, $families['Instantiable']);
    }
}
