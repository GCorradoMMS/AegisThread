<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ohttp;

/**
 * An OHTTP-encapsulated response as an opaque byte string
 * (ADR-0005, OQ-03/OQ-08/OQ-15).
 *
 * Application-owned type — deliberately NOT PSR-7. Bytes are passed through
 * uninterpreted by application code; only an adapter behind the
 * OhttpTransport seam may encode or decode them.
 *
 * Contract is provisional until the first consumer lands (M5); evolution
 * is a reviewed change.
 */
final readonly class EncapsulatedResponse
{
    public function __construct(
        public readonly string $bytes,
    ) {
    }
}
