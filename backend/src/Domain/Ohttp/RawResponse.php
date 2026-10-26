<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ohttp;

/**
 * A plain-HTTP response after OHTTP decapsulation (ADR-0005, OQ-03/OQ-08/OQ-15).
 *
 * Application-owned type — deliberately NOT PSR-7.
 *
 * Contract is provisional until the first consumer lands (M5); evolution
 * is a reviewed change.
 */
final readonly class RawResponse
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
    ) {
    }
}
