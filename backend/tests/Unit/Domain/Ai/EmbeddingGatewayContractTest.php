<?php

declare(strict_types=1);

namespace AegisThread\Tests\Unit\Domain\Ai;

use AegisThread\Domain\Ai\EmbeddingRequest;
use AegisThread\Domain\Ai\EmbeddingResult;
use AegisThread\Tests\Support\Consumers\EmbeddingConsumer;
use AegisThread\Tests\Support\Fakes\FakeEmbeddingGateway;
use PHPUnit\Framework\TestCase;

/**
 * Contract test for the EmbeddingGateway seam (ADR-0008, OQ-05): the
 * request/result DTOs flow through unchanged, vector values survive the
 * round trip, and the request object is never mutated. Embedding
 * dimensions are deliberately free — the fake returns a 3-float vector and
 * the contract says nothing about its length.
 */
final class EmbeddingGatewayContractTest extends TestCase
{
    public function testEmbeddingFlowsThroughTheSeamUnchanged(): void
    {
        $result = new EmbeddingResult([0.1, -0.2, 3.5]);
        $gateway = new FakeEmbeddingGateway($result);

        $returned = $gateway->embed(new EmbeddingRequest('Vectorize me.'));

        self::assertSame($result, $returned);
        self::assertSame([0.1, -0.2, 3.5], $returned->vector);
    }

    public function testRequestValuesSurviveTheRoundTripAndStayUnmutated(): void
    {
        $request = new EmbeddingRequest('Embed ADR-0005.');
        $gateway = new FakeEmbeddingGateway(new EmbeddingResult([1.0]));

        $returned = $gateway->embed($request);

        self::assertSame('Embed ADR-0005.', $request->text);
        self::assertSame('Embed ADR-0005.', $gateway->lastRequest?->text);
        self::assertSame([1.0], $returned->vector);
    }

    public function testDimensionsAreNotFixedByTheSeam(): void
    {
        // OQ-05: the embedding model fixes dimensions later; the seam must
        // accept any list<float>. Same request, differently sized vectors.
        $gateway = new FakeEmbeddingGateway(new EmbeddingResult([0.5, 0.5, 0.5, 0.5]));

        self::assertCount(4, $gateway->embed(new EmbeddingRequest('x'))->vector);

        $other = new FakeEmbeddingGateway(new EmbeddingResult([0.25]));
        self::assertCount(1, $other->embed(new EmbeddingRequest('x'))->vector);
    }

    public function testDomainConsumerEmbedsThroughTheSeamWithoutContainerOrInfrastructure(): void
    {
        $consumer = new EmbeddingConsumer(
            new FakeEmbeddingGateway(new EmbeddingResult([0.3, 0.4])),
        );

        self::assertSame([0.3, 0.4], $consumer->vectorFor('Prove it.'));
    }
}
