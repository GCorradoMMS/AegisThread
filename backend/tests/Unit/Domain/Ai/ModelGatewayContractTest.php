<?php

declare(strict_types=1);

namespace AegisThread\Tests\Unit\Domain\Ai;

use AegisThread\Domain\Ai\ModelCompletion;
use AegisThread\Domain\Ai\ModelRequest;
use AegisThread\Tests\Support\Consumers\ModelConsumer;
use AegisThread\Tests\Support\Fakes\FakeModelGateway;
use PHPUnit\Framework\TestCase;

/**
 * Contract test for the ModelGateway seam (ADR-0008): the request/response
 * DTOs flow through unchanged, values survive the round trip, and the
 * request object is never mutated by the gateway. Driven by a fake — no
 * container, no infrastructure, no provider.
 */
final class ModelGatewayContractTest extends TestCase
{
    public function testCompletionFlowsThroughTheSeamUnchanged(): void
    {
        $completion = new ModelCompletion('AegisThread says hello');
        $gateway = new FakeModelGateway($completion);

        $returned = $gateway->complete(new ModelRequest('Say hello.'));

        self::assertSame($completion, $returned);
        self::assertSame('AegisThread says hello', $returned->text);
    }

    public function testRequestValuesSurviveTheRoundTripAndStayUnmutated(): void
    {
        $request = new ModelRequest('Summarize ADR-0008.');
        $gateway = new FakeModelGateway(new ModelCompletion('done'));

        $returned = $gateway->complete($request);

        // The DTO is final readonly — language-level immutability. The
        // assertion documents that the contract REQUIRES the request to be
        // unmutated by the gateway call.
        self::assertSame('Summarize ADR-0008.', $request->prompt);
        self::assertSame('Summarize ADR-0008.', $gateway->lastRequest?->prompt);
        self::assertSame('done', $returned->text);
    }

    public function testDomainConsumerCompletesThroughTheSeamWithoutContainerOrInfrastructure(): void
    {
        $consumer = new ModelConsumer(
            new FakeModelGateway(new ModelCompletion(' inward dependency works')),
        );

        self::assertSame(
            ' inward dependency works',
            $consumer->complete('Prove it.'),
        );
    }
}
