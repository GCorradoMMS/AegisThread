<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Application\Configuration\PgvectorAvailabilityProbe;

/**
 * Test double for the PgvectorAvailabilityProbe port.
 */
final class FakePgvectorAvailabilityProbe implements PgvectorAvailabilityProbe
{
    public function __construct(
        private readonly ?string $version = '0.8.0',
        private readonly ?\Throwable $throwable = null,
    ) {
    }

    public function version(): ?string
    {
        if (null !== $this->throwable) {
            throw $this->throwable;
        }

        return $this->version;
    }
}
