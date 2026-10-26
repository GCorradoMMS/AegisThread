<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Port: runtime facts for the configuration report.
 *
 * Implemented by infrastructure (Symfony Kernel / PHP runtime).
 */
interface RuntimeInfoProvider
{
    /** Application environment name, e.g. 'dev', 'test', 'prod'. */
    public function environment(): string;

    /** PHP runtime version string, e.g. '8.5.12'. */
    public function phpVersion(): string;

    /** Framework version string, e.g. '8.1.9'. */
    public function frameworkVersion(): string;
}
