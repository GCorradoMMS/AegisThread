<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Configuration;

use AegisThread\Application\Configuration\ServerVersionReader;
use Doctrine\DBAL\Connection;

/**
 * Database server version through DBAL 4's Connection::getServerVersion()
 * (the connection initializes the platform from it). Any throwable —
 * including an unreachable database — maps to null.
 */
final class DbalServerVersionReader implements ServerVersionReader
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function serverVersion(): ?string
    {
        try {
            return $this->connection->getServerVersion();
        } catch (\Throwable) {
            return null;
        }
    }
}
