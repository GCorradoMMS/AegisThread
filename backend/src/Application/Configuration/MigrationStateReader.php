<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Port: state of the database migrations (doctrine-migrations).
 *
 * Implemented by infrastructure through the doctrine-migrations
 * MigrationsRepository/DependencyFactory API — never hand-rolled SQL.
 */
interface MigrationStateReader
{
    /** Number of executed migrations. */
    public function executedCount(): int;

    /** Version identifier of the most recently executed migration, null when none executed. */
    public function latestExecuted(): ?string;

    /** True when no available migration is still pending. */
    public function isUpToDate(): bool;
}
