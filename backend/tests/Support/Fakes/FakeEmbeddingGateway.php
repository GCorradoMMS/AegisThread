<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Domain\Ai\EmbeddingGateway;
use AegisThread\Domain\Ai\EmbeddingRequest;
use AegisThread\Domain\Ai\EmbeddingResult;

/**
 * Test double for the EmbeddingGateway seam: returns a fixed embedding and
 * records the last request for round-trip assertions.
 */
final class FakeEmbeddingGateway implements EmbeddingGateway
{
    public ?EmbeddingRequest $lastRequest = null;

    public function __construct(
        private readonly EmbeddingResult $result,
    ) {
    }

    public function embed(EmbeddingRequest $request): EmbeddingResult
    {
        $this->lastRequest = $request;

        return $this->result;
    }
}
