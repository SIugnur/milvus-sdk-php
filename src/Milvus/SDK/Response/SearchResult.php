<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\SearchResults as ProtoSearchResults;
use Milvus\SDK\Helpers\DataHelper;

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

    /**
     * Convert search results to an array of associative arrays (rows).
     *
     * Each row includes the field values, plus "id" and "score" keys.
     *
     * @param int|null $roundDecimal Number of decimal places to round scores to.
     * @return array<int, array<string, mixed>> Rows of data.
     */
    public function toArray(?int $roundDecimal = null): array
    {
        $results = $this->raw->getResults();
        if ($results === null) {
            return [];
        }

        $fieldsData = $results->getFieldsData();
        $fieldsData = $fieldsData instanceof \Google\Protobuf\Internal\RepeatedField
            ? iterator_to_array($fieldsData)
            : (array) $fieldsData;

        $rows = DataHelper::fieldDataToRows($fieldsData);

        // Attach IDs and scores to each row
        $ids = $this->getIds();
        $scores = $this->getScores();
        $topks = $results->getTopks();
        $topks = $topks ? iterator_to_array($topks) : [];

        // Group by field value (if present)
        $groupByFieldValue = $results->getGroupByFieldValue();
        $groupByValues = [];
        if ($groupByFieldValue !== null) {
            $groupByValues = DataHelper::extractFieldValues($groupByFieldValue);
        }

        $offset = 0;
        $numQueries = $this->getNumQueries();
        for ($q = 0; $q < $numQueries; $q++) {
            $k = $topks[$q] ?? 0;
            for ($j = 0; $j < $k; $j++) {
                $idx = $offset + $j;
                if (isset($rows[$idx])) {
                    $rows[$idx]['id'] = $ids[$idx] ?? null;
                    $score = $scores[$idx] ?? null;
                    if ($score !== null && $roundDecimal !== null) {
                        $score = round($score, $roundDecimal);
                    }
                    $rows[$idx]['score'] = $score;
                    if (!empty($groupByValues) && isset($groupByValues[$idx])) {
                        $rows[$idx]['group_by_field_value'] = $groupByValues[$idx];
                    }
                }
            }
            $offset += $k;
        }

        return $rows;
    }

    public function getRaw(): ProtoSearchResults
    {
        return $this->raw;
    }
}