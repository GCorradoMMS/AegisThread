<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Runtime section of the configuration report.
 */
final readonly class RuntimeSection implements \JsonSerializable
{
    public function __construct(
        public readonly string $php,
        public readonly string $framework,
    ) {
    }

    /**
     * @return array{php: string, framework: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'php' => $this->php,
            'framework' => $this->framework,
        ];
    }
}
