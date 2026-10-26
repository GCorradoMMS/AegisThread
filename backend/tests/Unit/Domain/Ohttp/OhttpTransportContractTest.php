<?php

declare(strict_types=1);

namespace AegisThread\Tests\Unit\Domain\Ohttp;

use AegisThread\Domain\Ohttp\EncapsulatedRequest;
use AegisThread\Domain\Ohttp\EncapsulatedResponse;
use AegisThread\Domain\Ohttp\RawRequest;
use AegisThread\Domain\Ohttp\RawResponse;
use AegisThread\Tests\Support\Consumers\OhttpConsumer;
use AegisThread\Tests\Support\Fakes\FakeOhttpTransport;
use PHPUnit\Framework\TestCase;

/**
 * Contract test for the OhttpTransport seam (ADR-0005, OQ-03/OQ-08/OQ-15):
 * application-owned DTOs flow through, values survive round trips unmutated,
 * and encapsulated payloads are opaque application-owned byte strings —
 * never PSR-7 or library types.
 */
final class OhttpTransportContractTest extends TestCase
{
    private const RELAY = 'https://relay.example.internal/ohttp';

    public function testEncapsulationFlowsThroughTheSeamUnchanged(): void
    {
        $encapsulated = new EncapsulatedRequest("\xB1\x02opaque");
        $transport = new FakeOhttpTransport($encapsulated, new RawResponse(200, ''), new EncapsulatedResponse(''));

        $returned = $transport->encapsulate(new RawRequest('POST', '/v1/submissions', 'payload'));

        self::assertSame($encapsulated, $returned);
        self::assertSame("\xB1\x02opaque", $returned->bytes);
    }

    public function testDecapsulationFlowsThroughTheSeamUnchanged(): void
    {
        $response = new RawResponse(201, 'accepted-body');
        $transport = new FakeOhttpTransport(new EncapsulatedRequest(''), $response, new EncapsulatedResponse(''));

        $returned = $transport->decapsulate(new EncapsulatedResponse("\xC2\x99reply"));

        self::assertSame($response, $returned);
        self::assertSame(201, $returned->statusCode);
        self::assertSame('accepted-body', $returned->body);
    }

    public function testForwardingCarriesRequestAndRelayEndpointThrough(): void
    {
        $encapsulated = new EncapsulatedRequest("\xDE\xAD\xBE\xEF");
        $reply = new EncapsulatedResponse("\xCA\xFE");
        $transport = new FakeOhttpTransport(new EncapsulatedRequest(''), new RawResponse(200, ''), $reply);

        $returned = $transport->forwardThroughRelay($encapsulated, self::RELAY);

        self::assertSame($reply, $returned);
        self::assertNotNull($transport->lastForwardInput);
        self::assertSame($encapsulated, $transport->lastForwardInput['request']);
        self::assertSame(self::RELAY, $transport->lastForwardInput['relayEndpoint']);
    }

    public function testEncapsulatedBytesAreApplicationOwnedOpaqueTypes(): void
    {
        $transport = new FakeOhttpTransport(
            new EncapsulatedRequest('bytes-in'),
            new RawResponse(200, 'body'),
            new EncapsulatedResponse('bytes-out'),
        );

        $encapsulated = $transport->encapsulate(new RawRequest('POST', '/v1/submissions', 'p'));
        $reply = $transport->forwardThroughRelay($encapsulated, self::RELAY);

        // The only types crossing the seam are the seam's own DTOs — the
        // application owns them; PSR-7 or ohttphp types would fail this.
        self::assertTrue(\str_starts_with($encapsulated::class, 'AegisThread\\Domain\\Ohttp\\'), \sprintf(
            'Encapsulated request type %s must live in the application-owned OHTTP seam.',
            $encapsulated::class,
        ));
        self::assertTrue(\str_starts_with($reply::class, 'AegisThread\\Domain\\Ohttp\\'), \sprintf(
            'Encapsulated response type %s must live in the application-owned OHTTP seam.',
            $reply::class,
        ));

        // Opaque pass-through: the exact bytes in are the bytes out, uninterpreted.
        self::assertSame('bytes-in', $encapsulated->bytes);
        self::assertSame('bytes-out', $reply->bytes);
    }

    public function testRawRequestValuesSurviveTheRoundTripAndStayUnmutated(): void
    {
        $request = new RawRequest('POST', '/v1/submissions', 'submission-body');
        $transport = new FakeOhttpTransport(new EncapsulatedRequest('e'), new RawResponse(200, ''), new EncapsulatedResponse('r'));

        $transport->encapsulate($request);

        self::assertSame('POST', $request->method);
        self::assertSame('/v1/submissions', $request->target);
        self::assertSame('submission-body', $request->body);
        self::assertSame($request, $transport->lastEncapsulateInput);
    }

    public function testDomainConsumerSubmitsThroughTheSeamWithoutContainerOrInfrastructure(): void
    {
        $consumer = new OhttpConsumer(
            new FakeOhttpTransport(
                new EncapsulatedRequest('enc'),
                new RawResponse(200, ''),
                new EncapsulatedResponse('encapsulated-reply'),
            ),
        );

        self::assertSame(
            'encapsulated-reply',
            $consumer->submitThroughRelay(new RawRequest('POST', '/v1/submissions', 'b'), self::RELAY),
        );
    }

    public function testDomainConsumerDecapsulatesThroughTheSeam(): void
    {
        $consumer = new OhttpConsumer(
            new FakeOhttpTransport(
                new EncapsulatedRequest(''),
                new RawResponse(202, 'ok'),
                new EncapsulatedResponse(''),
            ),
        );

        self::assertSame(202, $consumer->decapsulateReply(new EncapsulatedResponse('reply-bytes')));
    }
}
