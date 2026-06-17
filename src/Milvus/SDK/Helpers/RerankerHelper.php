<?php
namespace Milvus\SDK\Helpers;

/**
 * Reranker factory for hybrid search.
 *
 * Provides static factory methods to create ranker configuration arrays
 * for use with hybridSearch().
 */
class RerankerHelper
{
    /**
     * Create a Reciprocal Rank Fusion (RRF) ranker.
     *
     * @param int $k The RRF parameter (default: 60).
     * @return array{strategy: string, params: array{k: int}}
     */
    public static function rrf(int $k = 60): array
    {
        return [
            'strategy' => 'rrf',
            'params' => ['k' => $k],
        ];
    }

    /**
     * Create a weighted ranker.
     *
     * @param float[] $weights Array of weights, one per sub-request.
     * @return array{strategy: string, params: array{weights: float[]}}
     */
    public static function weighted(array $weights): array
    {
        return [
            'strategy' => 'weighted',
            'params' => ['weights' => $weights],
        ];
    }
}
