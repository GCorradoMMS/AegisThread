<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ohttp;

/**
 * Application-owned OHTTP transport seam (ADR-0005, OQ-03, OQ-08, OQ-15).
 *
 * Application code depends only on this seam — never on the OHTTP library
 * API (`gcorradomms/ohttphp`), whose supply-chain posture is tracked in
 * OQ-03 and whose fork/replace contingencies are OQ-15. The library API may
 * appear exclusively in an adapter behind this interface (built in M5). The
 * relay endpoint travels as a plain string so gateway key advertising
 * (OQ-08) stays library-independent.
 *
 * The types exchanged are application-owned opaque byte strings —
 * deliberately NOT PSR-7 — so no HTTP library type can leak through.
 *
 * Contract is provisional until the first consumer lands (M5); evolution
 * is a reviewed change.
 */
interface OhttpTransport
{
    public function encapsulate(RawRequest $request): EncapsulatedRequest;

    public function decapsulate(EncapsulatedResponse $response): RawResponse;

    public function forwardThroughRelay(EncapsulatedRequest $request, string $relayEndpoint): EncapsulatedResponse;
}
