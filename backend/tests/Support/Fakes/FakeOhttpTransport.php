<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Domain\Ohttp\EncapsulatedRequest;
use AegisThread\Domain\Ohttp\EncapsulatedResponse;
use AegisThread\Domain\Ohttp\OhttpTransport;
use AegisThread\Domain\Ohttp\RawRequest;
use AegisThread\Domain\Ohttp\RawResponse;

/**
 * Test double for the OhttpTransport seam: fixed outcomes per operation,
 * recording each call's inputs for pass-through assertions. Bytes are
 * returned uninterpreted — exactly the seam's contract.
 */
final class FakeOhttpTransport implements OhttpTransport
{
    public ?RawRequest $lastEncapsulateInput = null;
    public ?EncapsulatedResponse $lastDecapsulateInput = null;

    /** @var array{request: EncapsulatedRequest, relayEndpoint: string}|null */
    public ?array $lastForwardInput = null;

    public function __construct(
        private readonly EncapsulatedRequest $encapsulationOutcome,
        private readonly RawResponse $decapsulationOutcome,
        private readonly EncapsulatedResponse $forwardOutcome,
    ) {
    }

    public function encapsulate(RawRequest $request): EncapsulatedRequest
    {
        $this->lastEncapsulateInput = $request;

        return $this->encapsulationOutcome;
    }

    public function decapsulate(EncapsulatedResponse $response): RawResponse
    {
        $this->lastDecapsulateInput = $response;

        return $this->decapsulationOutcome;
    }

    public function forwardThroughRelay(EncapsulatedRequest $request, string $relayEndpoint): EncapsulatedResponse
    {
        $this->lastForwardInput = ['request' => $request, 'relayEndpoint' => $relayEndpoint];

        return $this->forwardOutcome;
    }
}
