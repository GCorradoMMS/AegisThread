<?php

declare(strict_types=1);

namespace AegisThread\Application\Health;

/**
 * Port: availability probe for the PostgreSQL source of truth (ADR-0002).
 *
 * Implemented by infrastructure (DBAL); consumed by the CheckHealth use
 * case. Implementations must not throw to signal unavailability — they
 * return false — but the use case additionally contains throwables, because
 * a health probe that raises is, by definition, a degraded system.
 */
interface DatabaseHealthProbe
{
    public function isAvailable(): bool;
}
