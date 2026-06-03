<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\DescribeCollectionResponse;
use Milvus\Proto\Schema\CollectionSchema;

class CollectionInfo
{
    private DescribeCollectionResponse $raw;

    public function __construct(DescribeCollectionResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getName(): string
    {
        return $this->raw->getSchema()?->getName() ?? '';
    }

    public function getDescription(): string
    {
        return $this->raw->getSchema()?->getDescription() ?? '';
    }

    public function getCollectionId(): int
    {
        return $this->raw->getCollectionID();
    }

    public function getFields(): array
    {
        $fields = $this->raw->getSchema()?->getFields();
        if ($fields === null) {
            return [];
        }
        return $fields instanceof \Google\Protobuf\Internal\RepeatedField
            ? iterator_to_array($fields)
            : (array) $fields;
    }

    public function getSchema(): ?CollectionSchema
    {
        return $this->raw->getSchema();
    }

    public function getRaw(): DescribeCollectionResponse
    {
        return $this->raw;
    }
}