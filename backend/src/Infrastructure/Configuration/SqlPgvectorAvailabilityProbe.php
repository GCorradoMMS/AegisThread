<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Configuration;

use AegisThread\Application\Configuration\PgvectorAvailabilityProbe;
use Doctrine\DBAL\Connection;

/**
 * pgvector availability via pg_extension — no row, or any throwable
 * (including an unreachable database), maps to null.
 */
final class SqlPgvectorAvailabilityProbe implements PgvectorAvailabilityProbe
{
    private const VECTOR_EXTENSION_QUERY = "SELECT extversion FROM pg_extension WHERE extname = 'vector'";

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function version(): ?string
    {
        try {
            $version = $this->connection->fetchOne(self::VECTOR_EXTENSION_QUERY);

            // extversion is text; false means no row (extension absent).
            return \is_string($version) ? $version : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
