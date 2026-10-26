<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Configuration;

use AegisThread\Application\Configuration\RuntimeInfoProvider;
use AegisThread\Kernel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Runtime facts from the Symfony Kernel and the PHP runtime.
 *
 * The kernel environment is injected as the %kernel.environment% parameter;
 * PHP and framework versions come from the run-time constants.
 */
final class RuntimeInfoAdapter implements RuntimeInfoProvider
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function phpVersion(): string
    {
        return \PHP_VERSION;
    }

    public function frameworkVersion(): string
    {
        return Kernel::VERSION;
    }
}
