<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\FlushResponse;

class FlushResult
{
    private FlushResponse $raw;

    public function __construct(FlushResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getCollectionSegmentIDs(): array
    {
        $segments = $this->raw->getCollSegIDs();
        if ($segments === null) return [];
        $result = [];
        foreach ($segments as $key => $ids) {
            $result[$key] = $ids ? iterator_to_array($ids->getData()) : [];
        }
        return $result;
    }

    public function getCollectionFlushTs(): array
    {
        $flushTs = $this->raw->getCollFlushTs();
        if ($flushTs === null) return [];
        return array_map(function ($value) {
            return (int)$value;
        }, (array)$flushTs);
    }

    public function getRaw(): FlushResponse
    {
        return $this->raw;
    }
}
