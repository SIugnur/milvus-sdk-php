<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\QueryResults as ProtoQueryResults;

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
        return count($this->extractFieldValues($fieldsData[0]));
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
        $fields = $this->getFieldsData();
        if (empty($fields)) {
            return [];
        }

        $rowCount = $this->getRowCount();
        if ($rowCount === 0) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            $fieldName = $field->getFieldName();
            $values = $this->extractFieldValues($field);
            for ($i = 0; $i < count($values); $i++) {
                $result[$i][$fieldName] = $values[$i];
            }
        }
        return $result;
    }

    public function getRaw(): ProtoQueryResults
    {
        return $this->raw;
    }

    private function extractFieldValues($field): array
    {
        $scalars = $field->getScalars();
        if ($scalars === null) {
            return [];
        }

        if ($scalars->getLongData() !== null) {
            return iterator_to_array($scalars->getLongData()->getData());
        }
        if ($scalars->getIntData() !== null) {
            return iterator_to_array($scalars->getIntData()->getData());
        }
        if ($scalars->getStringData() !== null) {
            return iterator_to_array($scalars->getStringData()->getData());
        }
        if ($scalars->getFloatData() !== null) {
            return iterator_to_array($scalars->getFloatData()->getData());
        }
        if ($scalars->getDoubleData() !== null) {
            return iterator_to_array($scalars->getDoubleData()->getData());
        }
        if ($scalars->getBoolData() !== null) {
            return iterator_to_array($scalars->getBoolData()->getData());
        }

        return [];
    }
}