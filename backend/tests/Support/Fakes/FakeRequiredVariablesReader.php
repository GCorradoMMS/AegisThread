<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support\Fakes;

use AegisThread\Application\Configuration\RequiredVariablesReader;

/**
 * Test double for the RequiredVariablesReader port: a fixed name => set map.
 */
final class FakeRequiredVariablesReader implements RequiredVariablesReader
{
    /**
     * @param array<string, bool> $variables
     */
    public function __construct(
        private readonly array $variables = [
            'DATABASE_URL' => true,
            'APP_SECRET' => true,
        ],
    ) {
    }

    public function requiredVariables(): array
    {
        return $this->variables;
    }
}
