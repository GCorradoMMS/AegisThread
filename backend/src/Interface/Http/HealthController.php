<?php

declare(strict_types=1);

namespace AegisThread\Interface\Http;

use AegisThread\Application\Health\CheckHealth;
use AegisThread\Application\Health\HealthReport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/health — transport mapping only.
 *
 * Response contract (allowlist): JSON keys exactly `status`, `checks`,
 * `checkedAt` (RFC3339); HTTP 200 when healthy, 503 when degraded;
 * Cache-Control: no-store (Symfony's header bag normalizes the value to
 * 'no-store, private' — no explicit visibility directive defaults to
 * private). The body comes from the HealthReport allowlist — exception
 * text and stack traces can never appear.
 */
#[AsController]
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly CheckHealth $checkHealth,
    ) {
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $report = ($this->checkHealth)();

        return new JsonResponse(
            $report,
            $report->isHealthy() ? 200 : 503,
            ['Cache-Control' => 'no-store'],
        );
    }
}
