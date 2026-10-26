<?php

declare(strict_types=1);

namespace AegisThread\Interface\Http;

use AegisThread\Application\Configuration\ConfigurationReport;
use AegisThread\Application\Configuration\GetConfigurationReport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/configuration — transport mapping only.
 *
 * ALWAYS HTTP 200: a degraded database or pending migrations is report
 * DATA, not an outage of this endpoint. Cache-Control: no-store (Symfony's
 * header bag normalizes the value to 'no-store, private').
 *
 * Response contract (env-contract visibility, allowlist): exactly
 *
 *     {
 *       "environment": {"name": "...", "requiredVariables": {"DATABASE_URL": {"set": true}, ...}},
 *       "runtime": {"php": "...", "framework": "..."},
 *       "database": {"reachable": true, "serverVersion": "..."},
 *       "migrations": {"state": "up-to-date|pending|unknown", "executed": 0, "latest": null},
 *       "extensions": {"pgvector": {"available": true, "version": "..."}},
 *       "generatedAt": "RFC3339"
 *     }
 *
 * The environment section shows a `set` boolean per EnvContract variable —
 * never a value. The ConfigurationReport DTO is the allowlist; no secret
 * can be emitted through it (verified by the secret-leak test).
 */
#[AsController]
final class ConfigurationController extends AbstractController
{
    public function __construct(
        private readonly GetConfigurationReport $getConfigurationReport,
    ) {
    }

    #[Route('/api/configuration', name: 'api_configuration', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            ($this->getConfigurationReport)(),
            200,
            ['Cache-Control' => 'no-store'],
        );
    }
}
