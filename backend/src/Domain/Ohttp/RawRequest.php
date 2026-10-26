<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ohttp;

/**
 * A plain-HTTP request before OHTTP encapsulation (ADR-0005, OQ-03/OQ-08/OQ-15).
 *
 * Application-owned type — deliberately NOT PSR-7. The protected submission
 * path expresses its transport needs in application terms only.
 *
 * Contract is provisional until the first consumer lands (M5); evolution
 * is a reviewed change.
 */
final readonly class RawRequest
{
    public function __construct(
        public readonly string $method,
        public readonly string $target,
        public readonly string $body,
    ) {
    }
}
