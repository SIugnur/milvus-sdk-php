<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\ShowPartitionsResponse;

class PartitionList
{
    private ShowPartitionsResponse $raw;

    public function __construct(ShowPartitionsResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getPartitionNames(): array
    {
        $names = $this->raw->getPartitionNames();
        return $names ? iterator_to_array($names) : [];
    }

    public function getPartitionIDs(): array
    {
        $ids = $this->raw->getPartitionIDs();
        return $ids ? iterator_to_array($ids) : [];
    }

    public function getPartitions(): array
    {
        $names = $this->getPartitionNames();
        $ids = $this->getPartitionIDs();
        $result = [];
        foreach ($names as $i => $name) {
            $result[] = [
                'name' => $name,
                'id' => $ids[$i] ?? 0,
            ];
        }
        return $result;
    }

    public function getRaw(): ShowPartitionsResponse
    {
        return $this->raw;
    }
}
