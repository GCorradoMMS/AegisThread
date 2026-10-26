<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * Typed JSON-body decoding for HTTP tests: asserts the payload is a string
 * holding a JSON object and hands it back as an array PHPStan can follow.
 */
final class JsonBody extends Assert
{
    /**
     * @return array<array-key, mixed>
     */
    public static function decode(string|false $content): array
    {
        self::assertIsString($content);

        $decoded = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * A named section of the body, asserted to be an object (array).
     *
     * @param array<array-key, mixed> $body
     *
     * @return array<array-key, mixed>
     */
    public static function section(array $body, string $key): array
    {
        self::assertArrayHasKey($key, $body);
        self::assertIsArray($body[$key]);

        return $body[$key];
    }

    /**
     * @param array<array-key, mixed> $body
     *
     * @return array<array-key, mixed>
     */
    public static function nested(array $body, string ...$path): array
    {
        $current = $body;

        foreach ($path as $key) {
            self::assertTrue(\array_key_exists($key, $current), \sprintf('Missing key "%s" in path.', $key));
            self::assertIsArray($current[$key]);
            $current = $current[$key];
        }

        return $current;
    }
}
