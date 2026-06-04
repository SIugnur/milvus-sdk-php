<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetCollectionStatisticsResponse;

class CollectionStats
{
    private GetCollectionStatisticsResponse $raw;

    public function __construct(GetCollectionStatisticsResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getRowCount(): int
    {
        return (int)($this->getStat('row_count') ?? 0);
    }

    public function getDataSize(): int
    {
        return (int)($this->getStat('data_size') ?? 0);
    }

    public function getStat(string $key): ?string
    {
        $stats = $this->raw->getStats();
        if ($stats === null) return null;
        foreach ($stats as $kv) {
            if ($kv->getKey() === $key) {
                return $kv->getValue();
            }
        }
        return null;
    }

    public function getAllStats(): array
    {
        $stats = $this->raw->getStats();
        if ($stats === null) return [];
        $result = [];
        foreach ($stats as $kv) {
            $result[$kv->getKey()] = $kv->getValue();
        }
        return $result;
    }

    public function getRaw(): GetCollectionStatisticsResponse
    {
        return $this->raw;
    }
}
