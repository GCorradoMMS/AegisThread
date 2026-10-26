<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Health;

use AegisThread\Application\Health\DatabaseHealthProbe;
use Doctrine\DBAL\Connection;

/**
 * DBAL-backed availability probe for the PostgreSQL source of truth.
 *
 * Any throwable — including connection timeouts bounded by the DSN's
 * connect_timeout=2 — is caught and mapped to "unavailable"; this probe
 * never rethrows.
 */
final class DbalDatabaseHealthProbe implements DatabaseHealthProbe
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function isAvailable(): bool
    {
        try {
            return false !== $this->connection->fetchOne('SELECT 1');
        } catch (\Throwable) {
            return false;
        }
    }
}
