<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\DescribeIndexResponse;
use Milvus\Proto\Milvus\GetIndexStateResponse;

class IndexInfo
{
    private DescribeIndexResponse $raw;

    public function __construct(DescribeIndexResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getIndexDescriptions(): array
    {
        $descs = $this->raw->getIndexDescriptions();
        return $descs ? iterator_to_array($descs) : [];
    }

    public function getFieldName(): string
    {
        $descs = $this->getIndexDescriptions();
        return $descs[0]?->getFieldName() ?? '';
    }

    public function getIndexName(): string
    {
        $descs = $this->getIndexDescriptions();
        return $descs[0]?->getIndexName() ?? '';
    }

    public function getIndexType(): string
    {
        $descs = $this->getIndexDescriptions();
        $params = $descs[0]?->getParams();
        if ($params) {
            foreach ($params as $kv) {
                if ($kv->getKey() === 'index_type') {
                    return $kv->getValue();
                }
            }
        }
        return '';
    }

    public function getRaw(): DescribeIndexResponse
    {
        return $this->raw;
    }

    public static function fromState(\Milvus\Proto\Milvus\GetIndexStateResponse $resp): int
    {
        return $resp->getState();
    }
}