<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ai;

/**
 * A prompt submitted for completion (ADR-0008, OQ-05).
 *
 * Application-owned input type: no provider request shape leaks inward.
 *
 * Contract is provisional until the first consumer lands (M2/M3); evolution
 * is a reviewed change.
 */
final readonly class ModelRequest
{
    public function __construct(
        public readonly string $prompt,
    ) {
    }
}
