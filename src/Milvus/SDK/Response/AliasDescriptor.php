<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\DescribeAliasResponse;

class AliasDescriptor
{
    private DescribeAliasResponse $raw;

    public function __construct(DescribeAliasResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getAlias(): string
    {
        return $this->raw->getAlias();
    }

    public function getCollectionName(): string
    {
        return $this->raw->getCollection();
    }

    public function getRaw(): DescribeAliasResponse
    {
        return $this->raw;
    }
}
