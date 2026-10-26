<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ai;

/**
 * Provider-neutral access to text embeddings (ADR-0008, OQ-05).
 *
 * The only way application code may compute embeddings. The embedding-model
 * choice — and therefore the result dimensions — is OQ-05; this seam
 * deliberately does not fix dimensions so the schema freeze (M2) is the only
 * place that must care.
 *
 * Contract is provisional until the first consumer lands (M2/M3); evolution
 * is a reviewed change.
 */
interface EmbeddingGateway
{
    public function embed(EmbeddingRequest $request): EmbeddingResult;
}
