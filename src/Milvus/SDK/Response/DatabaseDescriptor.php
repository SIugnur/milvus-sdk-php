<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\DescribeDatabaseResponse;

class DatabaseDescriptor
{
    private DescribeDatabaseResponse $raw;

    public function __construct(DescribeDatabaseResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getName(): string
    {
        return $this->raw->getDbName();
    }

    public function getDbName(): string
    {
        return $this->getName();
    }

    public function getProperties(): array
    {
        $props = $this->raw->getProperties();
        if ($props === null) {
            return [];
        }
        $result = [];
        foreach ($props as $kv) {
            $result[$kv->getKey()] = $kv->getValue();
        }
        return $result;
    }

    public function getRaw(): DescribeDatabaseResponse
    {
        return $this->raw;
    }
}
