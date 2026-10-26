<?php

declare(strict_types=1);

namespace AegisThread\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Scaffold smoke test: the service container compiles and boots in the test
 * environment with the current bundle and package configuration (including
 * Doctrine and the stdout-only Monolog setup).
 *
 * No HTTP call, no database connection — those arrive with real functional
 * tests in later passes. Replace freely as features land.
 */
final class KernelBootsTest extends KernelTestCase
{
    public function testKernelBootsInTestEnvironment(): void
    {
        $kernel = self::bootKernel();

        self::assertSame('test', $kernel->getEnvironment());
    }
}
