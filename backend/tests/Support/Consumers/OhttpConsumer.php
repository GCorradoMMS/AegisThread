<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Consumers;

use AegisThread\Domain\Ohttp\EncapsulatedResponse;
use AegisThread\Domain\Ohttp\OhttpTransport;
use AegisThread\Domain\Ohttp\RawRequest;

/**
 * Tiny domain consumer for the OhttpTransport seam: constructor-injected
 * with the interface only, so instantiating it against a fake — without any
 * container or infrastructure — proves the dependency points inward.
 */
final class OhttpConsumer
{
    public function __construct(
        private readonly OhttpTransport $ohttpTransport,
    ) {
    }

    /**
     * Encapsulate a request and forward it through a relay; returns the
     * encapsulated reply bytes untouched.
     */
    public function submitThroughRelay(RawRequest $request, string $relayEndpoint): string
    {
        $encapsulated = $this->ohttpTransport->encapsulate($request);
        $reply = $this->ohttpTransport->forwardThroughRelay($encapsulated, $relayEndpoint);

        return $reply->bytes;
    }

    public function decapsulateReply(EncapsulatedResponse $reply): int
    {
        return $this->ohttpTransport->decapsulate($reply)->statusCode;
    }
}
