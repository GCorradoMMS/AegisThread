<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Application\Configuration\MigrationStateReader;

/**
 * Test double for the MigrationStateReader port: programmable state,
 * optionally throwing to verify the 'unknown' mapping.
 */
final class FakeMigrationStateReader implements MigrationStateReader
{
    public function __construct(
        private readonly bool $upToDate = true,
        private readonly int $executed = 3,
        private readonly ?string $latest = '20260101000000',
        private readonly ?\Throwable $throwable = null,
    ) {
    }

    public function executedCount(): int
    {
        if (null !== $this->throwable) {
            throw $this->throwable;
        }

        return $this->executed;
    }

    public function latestExecuted(): ?string
    {
        if (null !== $this->throwable) {
            throw $this->throwable;
        }

        return $this->latest;
    }

    public function isUpToDate(): bool
    {
        if (null !== $this->throwable) {
            throw $this->throwable;
        }

        return $this->upToDate;
    }
}
