<?php

declare(strict_types=1);

namespace AegisThread\Application\Health;

/**
 * Use case: assess overall application health from individual checks.
 *
 * Mapping: the database probe being up yields a healthy report, anything
 * else (probe reporting down, or the probe itself throwing) yields a
 * degraded report. No exception ever escapes this use case — degraded data
 * is the answer to a health question, an exception is not.
 */
final class CheckHealth
{
    public function __construct(
        private readonly DatabaseHealthProbe $databaseHealthProbe,
    ) {
    }

    public function __invoke(): HealthReport
    {
        $databaseUp = false;

        try {
            $databaseUp = $this->databaseHealthProbe->isAvailable();
        } catch (\Throwable) {
            $databaseUp = false;
        }

        $databaseCheck = $databaseUp ? HealthReport::CHECK_UP : HealthReport::CHECK_DOWN;
        $status = $databaseUp ? HealthReport::STATUS_HEALTHY : HealthReport::STATUS_DEGRADED;

        return new HealthReport(
            status: $status,
            checks: [HealthReport::CHECK_DATABASE => $databaseCheck],
            checkedAt: new \DateTimeImmutable(),
        );
    }
}
