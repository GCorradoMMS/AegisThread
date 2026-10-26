<?php

declare(strict_types=1);

namespace AegisThread\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;

/**
 * Type-level seam purity: every parameter, return, and property type in the
 * Domain seams (src/Domain/Ai, src/Domain/Ohttp) must be a built-in type or
 * a type living in AegisThread\Domain\*.
 *
 * This catches type-level leakage (a stray Symfony/Doctrine/PSR type hint)
 * that a use-statement scan misses.
 */
final class SeamTypePurityTest extends TestCase
{
    /** Seam directories under src/, relative. */
    private const SEAM_DIRECTORIES = ['Domain/Ai', 'Domain/Ohttp'];

    /** PHP type keywords that are not "builtin" per reflection but are pure. */
    private const RELATIVE_TYPE_KEYWORDS = ['self', 'static', 'parent'];

    public function testSeamDirectoriesContainTypes(): void
    {
        self::assertGreaterThan(
            0,
            $this->seamClasses(),
            'The Domain seams are empty — the type-purity guard is scanning nothing.',
        );
    }

    public function testSeamTypesUseOnlyBuiltinsOrDomainTypes(): void
    {
        $violations = [];

        foreach ($this->seamClasses() as $class) {
            $violations = \array_merge($violations, $this->violationsIn($class));
        }

        self::assertSame(
            [],
            $violations,
            \sprintf(
                "Domain seam types leaked non-domain types:\n%s",
                \implode("\n", \array_map(static fn (string $v): string => '  - '.$v, $violations)),
            ),
        );
    }

    /**
     * @return list<class-string>
     */
    private function seamClasses(): array
    {
        $projectDir = dirname(__DIR__, 3); // tests/Unit/Domain → backend
        $classes = [];

        foreach (self::SEAM_DIRECTORIES as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $projectDir.'/src/'.$directory,
                    \RecursiveDirectoryIterator::SKIP_DOTS,
                ),
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo
                    || !$file->isFile()
                    || 'php' !== $file->getExtension()
                ) {
                    continue;
                }

                $relative = \substr($file->getPathname(), \strlen($projectDir.'/'), -4);
                $fqcn = 'AegisThread\\'.\str_replace('/', '\\', $relative);

                if (\class_exists($fqcn) || \interface_exists($fqcn)) {
                    $classes[] = $fqcn;
                }
            }
        }

        return $classes;
    }

    /**
     * @param class-string $fqcn
     *
     * @return list<string>
     */
    private function violationsIn(string $fqcn): array
    {
        $violations = [];

        if (!\str_starts_with($fqcn, 'AegisThread\\Domain\\')) {
            $violations[] = \sprintf('%s lives outside the Domain namespace.', $fqcn);
        }

        $reflection = new \ReflectionClass($fqcn);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $fqcn) {
                continue;
            }

            foreach ($method->getParameters() as $parameter) {
                $violations = \array_merge(
                    $violations,
                    $this->typeViolations(
                        $parameter->getType(),
                        \sprintf('%s::%s($%s)', $fqcn, $method->getName(), $parameter->getName()),
                    ),
                );
            }

            $violations = \array_merge(
                $violations,
                $this->typeViolations(
                    $method->getReturnType(),
                    \sprintf('%s::%s(): return', $fqcn, $method->getName()),
                ),
            );
        }

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $violations = \array_merge(
                $violations,
                $this->typeViolations(
                    $property->getType(),
                    \sprintf('%s::$%s', $fqcn, $property->getName()),
                ),
            );
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function typeViolations(?\ReflectionType $type, string $where): array
    {
        if (null === $type) {
            return [\sprintf('%s has no type declaration.', $where)];
        }

        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            $violations = [];

            foreach ($type->getTypes() as $member) {
                $violations = \array_merge($violations, $this->typeViolations($member, $where));
            }

            return $violations;
        }

        if (!$type instanceof \ReflectionNamedType) {
            return [\sprintf('%s has an unanalysable type.', $where)];
        }

        $name = $type->getName();

        if ($type->isBuiltin()) {
            return [];
        }

        foreach (self::RELATIVE_TYPE_KEYWORDS as $keyword) {
            if ($keyword === $name) {
                return [];
            }
        }

        if (\str_starts_with($name, 'AegisThread\\Domain\\')) {
            return [];
        }

        return [\sprintf('%s uses non-domain type %s.', $where, $name)];
    }
}
