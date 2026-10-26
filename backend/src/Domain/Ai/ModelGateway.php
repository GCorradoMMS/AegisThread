<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ai;

/**
 * Provider-neutral access to text completion (ADR-0008, OQ-05).
 *
 * The only way application code may reach a language model. Provider-specific
 * code lives exclusively in adapters behind this seam; no provider is named
 * here. Privacy terms of the eventual provider are recorded at this boundary.
 *
 * Contract is provisional until the first consumer lands (M2/M3); evolution
 * is a reviewed change.
 */
interface ModelGateway
{
    public function complete(ModelRequest $request): ModelCompletion;
}
