<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Port: availability of the pgvector extension (ADR-0002, Proposed).
 *
 * Implemented by infrastructure (SQL against pg_extension). Returns null
 * when the extension is not installed — or when availability cannot be
 * determined; implementers must not throw.
 */
interface PgvectorAvailabilityProbe
{
    /** Extension version string, or null when pgvector is unavailable. */
    public function version(): ?string;
}
