<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support;

/**
 * Immutable outcome of an in-process console command run (CommandTester).
 */
final readonly class ConsoleResult
{
    public function __construct(
        public string $output,
        public int $exitCode,
    ) {
    }
}
