<?php

declare(strict_types=1);

namespace AegisThread\Tests\Functional;

use AegisThread\Application\Health\DatabaseHealthProbe;
use AegisThread\Tests\Support\Fakes\FakeDatabaseHealthProbe;
use AegisThread\Tests\Support\JsonBody;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * GET /api/health over the kernel: 200 with an up probe, 503 with a down or
 * throwing probe, exact body allowlist, no-store, and no exception text —
 * with the probe swapped for a fake at the port, no database needed.
 */
final class HealthEndpointTest extends WebTestCase
{
    public function testHealthyProbeYields200(): void
    {
        $client = self::createClient();
        self::getContainer()->set(
            DatabaseHealthProbe::class,
            new FakeDatabaseHealthProbe(available: true),
        );

        $client->request('GET', '/api/health');
        $response = $client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        // Symfony 8.1's ResponseHeaderBag cannot emit a bare 'no-store': with
        // no explicit visibility directive it appends the conservative ', private'
        // (ResponseHeaderBag::computeCacheControlValue()). The no-store directive
        // is the contract; the appended visibility only strengthens it.
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));

        $body = JsonBody::decode($response->getContent());
        self::assertSame(['status', 'checks', 'checkedAt'], \array_keys($body));
        self::assertArrayHasKey('status', $body);
        self::assertSame('healthy', $body['status']);
        self::assertArrayHasKey('checks', $body);
        self::assertSame(['database' => 'up'], $body['checks']);
    }

    public function testUnavailableProbeYields503WithExactBodyKeys(): void
    {
        $client = self::createClient();
        self::getContainer()->set(
            DatabaseHealthProbe::class,
            new FakeDatabaseHealthProbe(available: false),
        );

        $client->request('GET', '/api/health');
        $response = $client->getResponse();

        self::assertSame(503, $response->getStatusCode());
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));

        $body = JsonBody::decode($response->getContent());
        self::assertSame(['status', 'checks', 'checkedAt'], \array_keys($body));
        self::assertArrayHasKey('status', $body);
        self::assertSame('degraded', $body['status']);
        self::assertArrayHasKey('checks', $body);
        self::assertSame(['database' => 'down'], $body['checks']);
    }

    public function testThrowingProbeIsContainedEndpointStillAnswersClean503(): void
    {
        $marker = 'probe-exploded-with-this-unique-marker-1f77';

        $client = self::createClient();
        self::getContainer()->set(
            DatabaseHealthProbe::class,
            new FakeDatabaseHealthProbe(throwable: new \RuntimeException($marker)),
        );

        $client->request('GET', '/api/health');
        $response = $client->getResponse();

        self::assertSame(503, $response->getStatusCode());

        $body = JsonBody::decode($response->getContent());
        self::assertSame(['status', 'checks', 'checkedAt'], \array_keys($body));
        self::assertArrayHasKey('status', $body);
        self::assertSame('degraded', $body['status']);

        self::assertIsString($response->getContent());
        $content = $response->getContent();
        self::assertStringNotContainsString($marker, $content);
        self::assertStringNotContainsString('RuntimeException', $content);
        self::assertStringNotContainsString('Exception', $content);
        self::assertStringNotContainsString('trace', $content);
    }

    public function testResponseBodyIsStableJsonOfTheAllowlist(): void
    {
        $client = self::createClient();
        self::getContainer()->set(
            DatabaseHealthProbe::class,
            new FakeDatabaseHealthProbe(available: true),
        );

        $client->request('GET', '/api/health');

        // json_encode of the decoded body round-trips — no stray keys were added.
        $body = JsonBody::decode($client->getResponse()->getContent());
        self::assertSame(
            $body,
            JsonBody::decode(json_encode($body, \JSON_THROW_ON_ERROR)),
        );
    }
}
