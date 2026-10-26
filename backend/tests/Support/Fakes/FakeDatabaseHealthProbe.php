<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Application\Health\DatabaseHealthProbe;

/**
 * Test double for the DatabaseHealthProbe port: programmable outcome,
 * optionally throwing to verify exception containment.
 */
final class FakeDatabaseHealthProbe implements DatabaseHealthProbe
{
    public function __construct(
        private readonly bool $available = true,
        private readonly ?\Throwable $throwable = null,
    ) {
    }

    public function isAvailable(): bool
    {
        if (null !== $this->throwable) {
            throw $this->throwable;
        }

        return $this->available;
    }
}
