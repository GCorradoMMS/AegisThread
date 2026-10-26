<?php

declare(strict_types=1);

namespace AegisThread\Tests\Functional;

use AegisThread\Infrastructure\Env\EnvContract;
use AegisThread\Tests\Support\JsonBody;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * GET /api/configuration over the kernel: always HTTP 200 (DB-down is
 * data), Cache-Control no-store, all five sections plus generatedAt, and
 * per-variable set/unset visibility without values. Runs against the real
 * adapters — with no database in the test environment, degraded sections
 * are the expected outcome.
 */
final class ConfigurationEndpointTest extends WebTestCase
{
    public function testReportAlwaysAnswers200WithAllSections(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/configuration');
        $response = $client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        // See HealthEndpointTest: Symfony's header bag computes 'no-store, private'.
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));

        $body = JsonBody::decode($response->getContent());

        self::assertSame(
            ['environment', 'runtime', 'database', 'migrations', 'extensions', 'generatedAt'],
            \array_keys($body),
        );
        $environment = JsonBody::section($body, 'environment');
        self::assertArrayHasKey('name', $environment);
        self::assertSame('test', $environment['name']);
        self::assertSame(['php', 'framework'], \array_keys(JsonBody::section($body, 'runtime')));
        self::assertSame(['reachable', 'serverVersion'], \array_keys(JsonBody::section($body, 'database')));
        self::assertSame(['state', 'executed', 'latest'], \array_keys(JsonBody::section($body, 'migrations')));
        self::assertSame(['pgvector'], \array_keys(JsonBody::section($body, 'extensions')));
    }

    public function testRequiredVariablesAreVisibleAsSetFlagsOnly(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/configuration');
        $body = JsonBody::decode($client->getResponse()->getContent());

        $requiredVariables = JsonBody::section(JsonBody::section($body, 'environment'), 'requiredVariables');

        foreach (EnvContract::REQUIRED as $name => $purpose) {
            self::assertArrayHasKey($name, $requiredVariables);
            self::assertIsArray($requiredVariables[$name]);
            self::assertSame(['set'], \array_keys($requiredVariables[$name]));
            self::assertArrayHasKey('set', $requiredVariables[$name]);
            self::assertIsBool($requiredVariables[$name]['set']);
        }
    }

    public function testDatabaseDownIsDataNotAnOutage(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/configuration');
        $body = JsonBody::decode($client->getResponse()->getContent());

        // No database in the unit/test environment: the degraded values are
        // the CONTRACT — degraded data, never an error page.
        $database = JsonBody::section($body, 'database');
        self::assertArrayHasKey('reachable', $database);
        self::assertFalse($database['reachable']);
        self::assertArrayHasKey('serverVersion', $database);
        self::assertNull($database['serverVersion']);

        $migrations = JsonBody::section($body, 'migrations');
        self::assertArrayHasKey('state', $migrations);
        self::assertSame('unknown', $migrations['state']);

        $pgvector = JsonBody::section(JsonBody::section($body, 'extensions'), 'pgvector');
        self::assertArrayHasKey('available', $pgvector);
        self::assertFalse($pgvector['available']);
        self::assertArrayHasKey('version', $pgvector);
        self::assertNull($pgvector['version']);
    }
}
