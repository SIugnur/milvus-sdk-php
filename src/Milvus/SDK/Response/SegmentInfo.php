<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetPersistentSegmentInfoResponse;
use Milvus\Proto\Milvus\GetQuerySegmentInfoResponse;

class SegmentInfo
{
    private object $raw;

    public function __construct(object $raw)
    {
        $this->raw = $raw;
    }

    public function getSegments(): array
    {
        if ($this->raw instanceof GetPersistentSegmentInfoResponse) {
            return $this->extractPersistentSegments();
        }
        if ($this->raw instanceof GetQuerySegmentInfoResponse) {
            return $this->extractQuerySegments();
        }
        return [];
    }

    private function extractPersistentSegments(): array
    {
        $infos = $this->raw->getInfos();
        if ($infos === null) return [];
        $result = [];
        foreach ($infos as $info) {
            $result[] = [
                'id' => $info->getSegmentID(),
                'collection_id' => $info->getCollectionID(),
                'partition_id' => $info->getPartitionID(),
                'num_rows' => $info->getNumRows(),
                'state' => $info->getState(),
                'state_name' => $info->getStateName(),
            ];
        }
        return $result;
    }

    private function extractQuerySegments(): array
    {
        $infos = $this->raw->getInfos();
        if ($infos === null) return [];
        $result = [];
        foreach ($infos as $info) {
            $result[] = [
                'id' => $info->getSegmentID(),
                'collection_id' => $info->getCollectionID(),
                'partition_id' => $info->getPartitionID(),
                'num_rows' => $info->getNumRows(),
                'state' => $info->getState(),
                'node_id' => $info->getNodeID(),
            ];
        }
        return $result;
    }

    public function getRaw(): object
    {
        return $this->raw;
    }
}
