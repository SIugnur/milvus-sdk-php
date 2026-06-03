<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\SearchResults as ProtoSearchResults;

class SearchResult
{
    private ProtoSearchResults $raw;

    public function __construct(ProtoSearchResults $raw)
    {
        $this->raw = $raw;
    }

    public function getResults()
    {
        return $this->raw->getResults();
    }

    public function getTopK(): int
    {
        return $this->raw->getResults()?->getTopK() ?? 0;
    }

    public function getNumQueries(): int
    {
        return $this->raw->getResults()?->getNumQueries() ?? 0;
    }

    public function getIds(): array
    {
        $ids = $this->raw->getResults()?->getIds();
        if ($ids === null) {
            return [];
        }
        if ($ids->getIntId() !== null) {
            return iterator_to_array($ids->getIntId()->getData());
        }
        if ($ids->getStrId() !== null) {
            return iterator_to_array($ids->getStrId()->getData());
        }
        return [];
    }

    public function getScores(): array
    {
        $scores = $this->raw->getResults()?->getScores();
        return $scores ? iterator_to_array($scores) : [];
    }

    public function getRaw(): ProtoSearchResults
    {
        return $this->raw;
    }
}