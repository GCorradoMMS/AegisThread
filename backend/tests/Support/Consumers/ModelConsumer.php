<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Consumers;

use AegisThread\Domain\Ai\ModelGateway;
use AegisThread\Domain\Ai\ModelRequest;

/**
 * Tiny domain consumer for the ModelGateway seam: constructor-injected with
 * the interface only, so instantiating it against a fake — without any
 * container or infrastructure — proves the dependency points inward.
 */
final class ModelConsumer
{
    public function __construct(
        private readonly ModelGateway $modelGateway,
    ) {
    }

    public function complete(string $prompt): string
    {
        return $this->modelGateway->complete(new ModelRequest($prompt))->text;
    }
}
