<?php

declare(strict_types=1);

namespace AegisThread\Tests\Boundary;

use PHPUnit\Framework\TestCase;

/**
 * Supply-chain boundary: none of the external capabilities this project is
 * architecturally forbidden to couple to (AI providers, the OHTTP candidate
 * library, the MCP SDK) may appear in the dependency tree — not in
 * composer.json (require AND require-dev) and not in composer.lock.
 *
 * A match is a case-insensitive hit on the package NAME or DESCRIPTION, so
 * renamed or repackaged dependencies are still caught.
 */
final class NoForbiddenDependenciesTest extends TestCase
{
    /**
     * Forbidden package identifiers (or distinctive name fragments).
     *
     * Matching is case-insensitive substring against package name and
     * description: `openai` must also catch e.g. "acme/openai-client".
     */
    private const FORBIDDEN_PACKAGES = [
        'openai',
        'anthropic',
        'google-genai',
        'gcorradomms/ohttphp',
        'mcp/sdk',
    ];

    public function testComposerJsonContainsNoForbiddenPackages(): void
    {
        $composer = $this->readJson(__DIR__.'/../../composer.json');

        $declared = [];

        foreach (['require', 'require-dev'] as $section) {
            if (\is_array($composer[$section] ?? null)) {
                foreach ($composer[$section] as $name => $constraint) {
                    if (\is_string($name) && null !== $constraint) {
                        $declared[] = $name;
                    }
                }
            }
        }

        $this->assertNoMatches($declared, 'composer.json');
    }

    public function testComposerLockContainsNoForbiddenPackages(): void
    {
        $lock = $this->readJson(__DIR__.'/../../composer.lock');

        $locked = [];

        foreach (['packages', 'packages-dev'] as $section) {
            if (!\is_array($lock[$section] ?? null)) {
                continue;
            }

            foreach ($lock[$section] as $package) {
                if (!\is_array($package)) {
                    continue;
                }

                $name = $package['name'] ?? null;
                $description = $package['description'] ?? null;

                if (\is_string($name)) {
                    $locked[] = $name;
                }

                if (\is_string($description)) {
                    $locked[] = $description;
                }
            }
        }

        $this->assertNoMatches($locked, 'composer.lock');
    }

    /**
     * @param list<string> $candidates
     */
    private function assertNoMatches(array $candidates, string $source): void
    {
        $violations = [];

        foreach ($candidates as $candidate) {
            foreach (self::FORBIDDEN_PACKAGES as $forbidden) {
                if (\str_contains(\strtolower($candidate), $forbidden)) {
                    $violations[] = \sprintf('%s: "%s" matches forbidden "%s"', $source, $candidate, $forbidden);
                }
            }
        }

        self::assertSame(
            [],
            $violations,
            \sprintf(
                "Forbidden dependency candidates found:\n%s",
                \implode("\n", array_map(static fn (string $v): string => '  - '.$v, $violations)),
            ),
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function readJson(string $path): array
    {
        $contents = file_get_contents($path);
        self::assertIsString($contents, \sprintf('Unable to read %s.', $path));

        $decoded = \json_decode(\trim($contents), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
