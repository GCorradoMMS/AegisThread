<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Application\Configuration\ServerVersionReader;

/**
 * Test double for the ServerVersionReader port.
 */
final class FakeServerVersionReader implements ServerVersionReader
{
    public function __construct(
        private readonly ?string $version = '16.9',
    ) {
    }

    public function serverVersion(): ?string
    {
        return $this->version;
    }
}
