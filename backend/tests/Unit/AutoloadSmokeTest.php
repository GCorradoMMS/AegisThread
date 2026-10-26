<?php

declare(strict_types=1);

namespace AegisThread\Tests\Unit;

use AegisThread\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * Scaffold smoke test: the AegisThread\ PSR-4 mapping resolves src/ classes.
 *
 * Keeps the unit suite addressable before real unit tests arrive in later
 * passes. Replace freely as Domain unit tests land.
 */
final class AutoloadSmokeTest extends TestCase
{
    public function testKernelClassIsLoadableThroughProjectAutoloader(): void
    {
        self::assertTrue(
            class_exists(Kernel::class),
            sprintf('The project autoloader failed to resolve %s.', Kernel::class),
        );
    }
}
