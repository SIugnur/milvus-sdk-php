<?php
namespace Milvus\SDK\Helpers;

use Milvus\Proto\Common\KeyValuePair;
use Milvus\Proto\Milvus\InsertRequest;
use Milvus\Proto\Milvus\UpsertRequest;
use Milvus\Proto\Schema\FieldData;
use Milvus\Proto\Schema\FloatArray;
use Milvus\Proto\Schema\LongArray;
use Milvus\Proto\Schema\IntArray;
use Milvus\Proto\Schema\StringArray;
use Milvus\Proto\Schema\BoolArray;
use Milvus\Proto\Schema\BytesArray;
use Milvus\Proto\Schema\DoubleArray;
use Milvus\Proto\Schema\ScalarField;
use Milvus\Proto\Schema\IDs;
use Milvus\Proto\Schema\DataType;
use Milvus\SDK\Exceptions\ParamException;

class DataHelper
{
    public static function buildFieldData(string $fieldName, array $values, int $dataType): FieldData
    {
        $fd = new FieldData();
        $fd->setFieldName($fieldName);
        $fd->setType($dataType);

        switch ($dataType) {
            case DataType::Int64:
                $arr = new LongArray();
                $arr->setData($values);
                $fd->setScalars((new ScalarField())->setLongData($arr));
                break;
            case DataType::Int32:
            case DataType::Int16:
            case DataType::Int8:
                $arr = new IntArray();
                $arr->setData($values);
                $fd->setScalars((new ScalarField())->setIntData($arr));
                break;
            case DataType::Float:
                $arr = new FloatArray();
                $arr->setData($values);
                $fd->setScalars((new ScalarField())->setFloatData($arr));
                break;
            case DataType::Double:
                $arr = new DoubleArray();
                $arr->setData($values);
                $fd->setScalars((new ScalarField())->setDoubleData($arr));
                break;
            case DataType::Bool:
                $arr = new BoolArray();
                $arr->setData($values);
                $fd->setScalars((new ScalarField())->setBoolData($arr));
                break;
            case DataType::VarChar:
            case DataType::String:
                $arr = new StringArray();
                $arr->setData($values);
                $fd->setScalars((new ScalarField())->setStringData($arr));
                break;
            case DataType::FloatVector:
                $flat = [];
                foreach ($values as $vec) {
                    $flat = array_merge($flat, $vec);
                }
                $floatArray = new FloatArray();
                $floatArray->setData($flat);
                $vecField = new \Milvus\Proto\Schema\VectorField();
                $vecField->setFloatVector($floatArray);
                $vecField->setDim(count($values[0]));
                $fd->setVectors($vecField);
                break;
            case DataType::BinaryVector:
                $vecField = new \Milvus\Proto\Schema\VectorField();
                $vecField->setBinaryVector(implode('', $values));
                $fd->setVectors($vecField);
                break;
            default:
                throw new ParamException("Unsupported data type: $dataType");
        }

        return $fd;
    }

    public static function recordsToFieldData(array $records, array $schema): array
    {
        if (empty($records)) {
            throw new ParamException('Records cannot be empty');
        }

        $fields = [];
        foreach ($schema as $fieldName => $dataType) {
            $values = [];
            foreach ($records as $record) {
                if (isset($record[$fieldName])) {
                    $values[] = $record[$fieldName];
                } else {
                    $values[] = null;
                }
            }
            $fields[] = self::buildFieldData($fieldName, array_values(array_filter($values, fn($v) => $v !== null)), $dataType);
        }

        return $fields;
    }
}