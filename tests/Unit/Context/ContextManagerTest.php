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

namespace Sidus\EAVModelBundle\Tests\Unit\Context;

use PHPUnit\Framework\TestCase;
use Sidus\EAVModelBundle\Context\ContextManager;

class ContextManagerTest extends TestCase
{
    public function testDefaultContext(): void
    {
        $defaultContext = ['locale' => 'en', 'channel' => 'web'];
        $manager = new ContextManager($defaultContext);

        $this->assertSame($defaultContext, $manager->getDefaultContext());
        $this->assertSame($defaultContext, $manager->getContext());
    }

    public function testSetContext(): void
    {
        $manager = new ContextManager(['locale' => 'en']);

        $newContext = ['locale' => 'fr', 'channel' => 'mobile'];
        $manager->setContext($newContext);

        $this->assertSame($newContext, $manager->getContext());
        // Default should remain unchanged
        $this->assertSame(['locale' => 'en'], $manager->getDefaultContext());
    }

    public function testGetContextValue(): void
    {
        $manager = new ContextManager(['locale' => 'en', 'channel' => 'web']);

        $this->assertSame('en', $manager->getContextValue('locale'));
        $this->assertSame('web', $manager->getContextValue('channel'));
        $this->assertNull($manager->getContextValue('nonexistent'));
    }

    public function testSetContextValue(): void
    {
        $manager = new ContextManager(['locale' => 'en']);

        $manager->setContextValue('locale', 'fr');
        $manager->setContextValue('channel', 'web');

        $this->assertSame('fr', $manager->getContextValue('locale'));
        $this->assertSame('web', $manager->getContextValue('channel'));
    }

    public function testMergeContext(): void
    {
        $manager = new ContextManager(['locale' => 'en', 'channel' => 'web']);

        $merged = $manager->mergeContext(['channel' => 'mobile', 'version' => '1.0']);

        $this->assertSame([
            'locale' => 'en',
            'channel' => 'mobile',
            'version' => '1.0',
        ], $merged);

        // Original context should be unchanged
        $this->assertSame([
            'locale' => 'en',
            'channel' => 'web',
        ], $manager->getContext());
    }
}
