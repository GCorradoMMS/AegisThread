<?php

declare(strict_types=1);

namespace AegisThread\Tests\Functional\Routing;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The application-owned route surface is EXACTLY {api_health,
 * api_configuration}: any additional application route would silently widen
 * the unauthenticated HTTP surface. Framework-internal underscore-prefixed
 * debug routes (e.g. _preview_error) are out of scope here — they are
 * excluded from the assertion by construction, not asserted absent.
 *
 * Boots the kernel in the test environment; no database involved.
 */
final class RouteSurfaceTest extends KernelTestCase
{
    public function testApplicationRoutesAreExactlyTheTwoDiagnosticEndpoints(): void
    {
        $names = \array_keys(self::getContainer()->get('router')->getRouteCollection()->all());
        \sort($names);

        $applicationRoutes = \array_values(\array_filter(
            $names,
            static fn (string $name): bool => !\str_starts_with($name, '_'),
        ));

        self::assertSame(['api_configuration', 'api_health'], $applicationRoutes);
    }
}
