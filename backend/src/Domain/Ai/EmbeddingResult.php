<?php

declare(strict_types=1);

namespace AegisThread\Domain\Ai;

/**
 * Embedding vector returned for a text (ADR-0008, OQ-05).
 *
 * The vector dimensions are deliberately NOT fixed by this type: the
 * embedding model is OQ-05 and its choice must only constrain the M2 schema
 * freeze, not the seam.
 *
 * Contract is provisional until the first consumer lands (M2/M3); evolution
 * is a reviewed change.
 */
final readonly class EmbeddingResult
{
    /**
     * @param list<float> $vector
     */
    public function __construct(
        public readonly array $vector,
    ) {
    }
}
