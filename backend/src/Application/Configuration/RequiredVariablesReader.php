<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Port: presence flags for the required environment variables.
 *
 * The environment contract itself (which variables are required) is owned by
 * infrastructure; this port exposes only names and a set/unset flag per
 * name. Variable VALUES must never cross this boundary — the report is a
 * visibility surface, not a disclosure surface.
 */
interface RequiredVariablesReader
{
    /**
     * @return array<string, bool> required variable name => whether it is set (never its value)
     */
    public function requiredVariables(): array;
}
