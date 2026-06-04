<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetReplicasResponse;

class ReplicaInfo
{
    private GetReplicasResponse $raw;

    public function __construct(GetReplicasResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getReplicas(): array
    {
        $replicas = $this->raw->getReplicas();
        if ($replicas === null) return [];
        $result = [];
        foreach ($replicas as $r) {
            $result[] = [
                'id' => $r->getReplicaID(),
                'collection_id' => $r->getCollectionID(),
                'node_ids' => $r->getNodeIds() ? iterator_to_array($r->getNodeIds()) : [],
            ];
        }
        return $result;
    }

    public function getRaw(): GetReplicasResponse
    {
        return $this->raw;
    }
}
