<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\QueryResults as ProtoQueryResults;
use Milvus\SDK\Helpers\DataHelper;

class QueryResult
{
    private ProtoQueryResults $raw;

    public function __construct(ProtoQueryResults $raw)
    {
        $this->raw = $raw;
    }

    public function getRowCount(): int
    {
        $fieldsData = $this->raw->getFieldsData();
        if ($fieldsData->count() === 0) {
            return 0;
        }
        return count(DataHelper::extractFieldValues($fieldsData[0]));
    }

    public function getFieldsData(): array
    {
        $fields = $this->raw->getFieldsData();
        return $fields instanceof \Google\Protobuf\Internal\RepeatedField
            ? iterator_to_array($fields)
            : (array) $fields;
    }

    public function toArray(): array
    {
        return DataHelper::fieldDataToRows($this->getFieldsData());
    }

    public function getRaw(): ProtoQueryResults
    {
        return $this->raw;
    }
}