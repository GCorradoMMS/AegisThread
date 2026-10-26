<?php

declare(strict_types=1);

namespace AegisThread\Tests\Boundary;

use PHPUnit\Framework\TestCase;

use function count;

use const T_USE;

/**
 * Architectural boundary: the Domain and Application layers must stay pure.
 *
 * Per AegisThread's Clean Architecture rules, neither layer may import
 * framework or infrastructure packages. This test scans every PHP file under
 * src/Domain and src/Application and fails if a top-level `use` statement
 * imports a Symfony, Doctrine, or PSR symbol, listing every offending
 * file and import.
 *
 * The scan is a pass when the layers are empty or do not exist yet, so the
 * guard is meaningful from the first real class onward without blocking the
 * scaffold.
 */
final class DomainAndApplicationPurityTest extends TestCase
{
    /**
     * Import namespaces forbidden in Domain and Application.
     *
     * Scope per plan: Symfony, Doctrine, and PSR imports only.
     */
    private const FORBIDDEN_IMPORT_PREFIXES = ['Symfony\\', 'Doctrine\\', 'Psr\\'];

    /** Layer directories (relative to src/), in report order. */
    private const PURITY_LAYERS = ['Domain', 'Application'];

    public function testDomainAndApplicationLayersDoNotImportFrameworkOrInfrastructurePackages(): void
    {
        $violations = [];

        foreach (self::PURITY_LAYERS as $layer) {
            $layerDir = \sprintf('%s/src/%s', $this->projectDir(), $layer);

            if (!\is_dir($layerDir)) {
                continue;
            }

            foreach ($this->phpFilesIn($layerDir) as $file) {
                foreach ($this->forbiddenImportsIn($file->getPathname()) as $import) {
                    $violations[] = \sprintf('%s: use %s;', $this->relativePath($file), $import);
                }
            }
        }

        self::assertSame(
            [],
            $violations,
            \sprintf(
                "Domain/Application purity violated. Forbidden imports found:\n%s",
                \implode("\n", \array_map(static fn (string $v): string => '  - '.$v, $violations)),
            ),
        );
    }

    private function projectDir(): string
    {
        // tests/Boundary/<this file> → backend root
        return dirname(__DIR__, 2);
    }

    private function relativePath(\SplFileInfo $file): string
    {
        return \substr($file->getPathname(), \strlen($this->projectDir().'/'));
    }

    /**
     * All PHP files under the given directory, recursively.
     *
     * @return list<\SplFileInfo>
     */
    private function phpFilesIn(string $dir): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Forbidden imports in a single file, as normalized import statements.
     *
     * Only top-level `use` statements (brace depth 0) count: trait uses and
     * closure `use (...)` captures live inside braces and are skipped.
     *
     * @return list<string>
     */
    private function forbiddenImportsIn(string $path): array
    {
        $contents = \file_get_contents($path);

        if (false === $contents) {
            return [];
        }

        $tokens = \token_get_all($contents);
        $offenders = [];
        $depth = 0;

        for ($i = 0, $n = \count($tokens); $i < $n; ++$i) {
            $token = $tokens[$i];

            if (\is_array($token)) {
                [$id] = $token;

                if (\T_CURLY_OPEN === $id || \T_DOLLAR_OPEN_CURLY_BRACES === $id) {
                    ++$depth;

                    continue;
                }

                if (\T_USE === $id && 0 === $depth) {
                    $import = $this->importAt($tokens, $i);

                    if (null !== $import && $this->isForbidden($import)) {
                        $offenders[] = $import;
                    }
                }

                continue;
            }

            if ('{' === $token) {
                ++$depth;
            } elseif ('}' === $token) {
                $depth = max(0, $depth - 1);
            }
        }

        return $offenders;
    }

    /**
     * Normalized import statement starting at the T_USE token at $index.
     *
     * Returns the imported symbol (e.g. "Symfony\Component\Uid\Uuid"),
     * or null when the statement is not an import (closure capture) or
     * cannot be read meaningfully. Group imports are cut at the brace
     * ("Symfony\Component\Serializer\{ ...") so the prefix remains checkable.
     *
     * @param list<array{int, string, int}|string> $tokens
     */
    private function importAt(array $tokens, int $index): ?string
    {
        $statement = '';

        for ($i = $index + 1, $n = \count($tokens); $i < $n; ++$i) {
            $token = $tokens[$i];

            if (\is_string($token)) {
                if (';' === $token || '{' === $token) {
                    break; // statement end; brace also completes a group-use prefix
                }

                break; // any other bare token ends the readable statement
            }

            [$id, $text] = $token;

            if (\T_WHITESPACE === $id) {
                $statement .= ' ';

                continue;
            }

            $isNamePart = \T_STRING === $id
                || \T_NS_SEPARATOR === $id
                || \T_NAME_QUALIFIED === $id
                || \T_NAME_FULLY_QUALIFIED === $id
                || \T_FUNCTION === $id
                || \T_CONST === $id;

            if (!$isNamePart) {
                break;
            }

            $statement .= $text;
        }

        $statement = \trim($statement);

        if ('' === $statement || \str_starts_with($statement, '(')) {
            return null; // closure `use (...)` capture, not an import
        }

        // "use function Foo\Bar" / "use const Foo\BAZ" — check the symbol.
        foreach (['function ', 'const '] as $keyword) {
            if (\str_starts_with($statement, $keyword)) {
                $statement = \substr($statement, \strlen($keyword));
                $statement = \trim($statement);

                break;
            }
        }

        return \ltrim($statement, '\\');
    }

    private function isForbidden(string $import): bool
    {
        foreach (self::FORBIDDEN_IMPORT_PREFIXES as $prefix) {
            if (\str_starts_with($import, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
