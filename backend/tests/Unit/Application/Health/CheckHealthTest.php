<?php

declare(strict_types=1);

namespace AegisThread\Tests\Unit\Application\Health;

use AegisThread\Application\Health\CheckHealth;
use AegisThread\Application\Health\HealthReport;
use AegisThread\Tests\Support\Fakes\FakeDatabaseHealthProbe;
use AegisThread\Tests\Support\JsonBody;
use PHPUnit\Framework\TestCase;

/**
 * CheckHealth mapping: probe up → healthy, probe down → degraded, probe
 * throwing → degraded. No exception ever escapes the use case.
 */
final class CheckHealthTest extends TestCase
{
    public function testProbeUpYieldsHealthyReport(): void
    {
        $report = (new CheckHealth(new FakeDatabaseHealthProbe(available: true)))();

        self::assertSame(HealthReport::STATUS_HEALTHY, $report->status);
        self::assertTrue($report->isHealthy());
        self::assertSame(
            [HealthReport::CHECK_DATABASE => HealthReport::CHECK_UP],
            $report->checks,
        );
    }

    public function testProbeDownYieldsDegradedReport(): void
    {
        $report = (new CheckHealth(new FakeDatabaseHealthProbe(available: false)))();

        self::assertSame(HealthReport::STATUS_DEGRADED, $report->status);
        self::assertFalse($report->isHealthy());
        self::assertSame(
            [HealthReport::CHECK_DATABASE => HealthReport::CHECK_DOWN],
            $report->checks,
        );
    }

    public function testProbeThrowingIsContainedAndYieldsDegradedReport(): void
    {
        $probe = new FakeDatabaseHealthProbe(
            throwable: new \RuntimeException('database exploded with marker text'),
        );

        $report = (new CheckHealth($probe))();

        self::assertSame(HealthReport::STATUS_DEGRADED, $report->status);
        self::assertSame(
            [HealthReport::CHECK_DATABASE => HealthReport::CHECK_DOWN],
            $report->checks,
        );
    }

    public function testJsonRepresentationIsTheExactAllowlist(): void
    {
        $report = (new CheckHealth(new FakeDatabaseHealthProbe(available: true)))();

        $decoded = JsonBody::decode(\json_encode($report, \JSON_THROW_ON_ERROR));

        self::assertSame(['status', 'checks', 'checkedAt'], \array_keys($decoded));
        self::assertIsString($decoded['checkedAt']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $decoded['checkedAt'],
            'checkedAt must be an RFC3339 timestamp.',
        );
    }
}
