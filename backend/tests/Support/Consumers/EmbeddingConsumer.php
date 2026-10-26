<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Consumers;

use AegisThread\Domain\Ai\EmbeddingGateway;
use AegisThread\Domain\Ai\EmbeddingRequest;

/**
 * Tiny domain consumer for the EmbeddingGateway seam: constructor-injected
 * with the interface only, so instantiating it against a fake — without any
 * container or infrastructure — proves the dependency points inward.
 */
final class EmbeddingConsumer
{
    public function __construct(
        private readonly EmbeddingGateway $embeddingGateway,
    ) {
    }

    /**
     * @return list<float>
     */
    public function vectorFor(string $text): array
    {
        return $this->embeddingGateway->embed(new EmbeddingRequest($text))->vector;
    }
}
