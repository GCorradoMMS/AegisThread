<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ai;

/**
 * Text returned by a completion (ADR-0008, OQ-05).
 *
 * Application-owned output type: no provider response shape leaks inward.
 *
 * Contract is provisional until the first consumer lands (M2/M3); evolution
 * is a reviewed change.
 */
final readonly class ModelCompletion
{
    public function __construct(
        public readonly string $text,
    ) {
    }
}
