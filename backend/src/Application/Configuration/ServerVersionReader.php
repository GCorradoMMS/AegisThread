<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Port: server version of the PostgreSQL source of truth (ADR-0002).
 *
 * Implemented by infrastructure (DBAL). Returns null when the version
 * cannot be determined; implementers must not throw.
 */
interface ServerVersionReader
{
    /** Database server version string, or null when unavailable. */
    public function serverVersion(): ?string;
}
