<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Domain\Ai\ModelCompletion;
use AegisThread\Domain\Ai\ModelGateway;
use AegisThread\Domain\Ai\ModelRequest;

/**
 * Test double for the ModelGateway seam: returns a fixed completion and
 * records the last request for round-trip assertions.
 */
final class FakeModelGateway implements ModelGateway
{
    public ?ModelRequest $lastRequest = null;

    public function __construct(
        private readonly ModelCompletion $completion,
    ) {
    }

    public function complete(ModelRequest $request): ModelCompletion
    {
        $this->lastRequest = $request;

        return $this->completion;
    }
}
