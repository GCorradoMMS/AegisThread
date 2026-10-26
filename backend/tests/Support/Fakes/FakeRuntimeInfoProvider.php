<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Application\Configuration\RuntimeInfoProvider;

/**
 * Test double for the RuntimeInfoProvider port.
 */
final class FakeRuntimeInfoProvider implements RuntimeInfoProvider
{
    public function __construct(
        private readonly string $environment = 'test',
        private readonly string $phpVersion = '8.5.12',
        private readonly string $frameworkVersion = '8.1.9',
    ) {
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function phpVersion(): string
    {
        return $this->phpVersion;
    }

    public function frameworkVersion(): string
    {
        return $this->frameworkVersion;
    }
}
