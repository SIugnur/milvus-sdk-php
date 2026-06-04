<?php
namespace Milvus\SDK\Helpers;

use Milvus\Proto\Schema\FieldData;
use Milvus\Proto\Schema\FloatArray;
use Milvus\Proto\Schema\LongArray;
use Milvus\Proto\Schema\IntArray;
use Milvus\Proto\Schema\StringArray;
use Milvus\Proto\Schema\BoolArray;
use Milvus\Proto\Schema\DoubleArray;
use Milvus\Proto\Schema\ScalarField;
use Milvus\Proto\Schema\VectorField;
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
                $dim = 0;
                foreach ($values as $vec) {
                    if (is_array($vec)) {
                        $flat = array_merge($flat, $vec);
                        if ($dim === 0) {
                            $dim = count($vec);
                        }
                    }
                }
                $floatArray = new FloatArray();
                $floatArray->setData($flat);
                $vecField = new \Milvus\Proto\Schema\VectorField();
                $vecField->setFloatVector($floatArray);
                $vecField->setDim($dim);
                $fd->setVectors($vecField);
                break;
            case DataType::SparseFloatVector:
                $contents = [];
                $dim = 0;
                foreach ($values as $sparseVec) {
                    $buf = '';
                    foreach ($sparseVec as $idx => $val) {
                        $buf .= pack('Vf', $idx, $val);
                        if ($idx + 1 > $dim) {
                            $dim = $idx + 1;
                        }
                    }
                    $contents[] = $buf;
                }
                $sparseArray = new \Milvus\Proto\Schema\SparseFloatArray();
                $sparseArray->setContents($contents);
                $sparseArray->setDim($dim);
                $vecField = new \Milvus\Proto\Schema\VectorField();
                $vecField->setSparseFloatVector($sparseArray);
                $vecField->setDim($dim);
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
                    $values[] = self::getDefaultValue($dataType);
                }
            }
            $fields[] = self::buildFieldData($fieldName, $values, $dataType);
        }

        return $fields;
    }

    private static function getDefaultValue(int $dataType)
    {
        switch ($dataType) {
            case DataType::Int64:
            case DataType::Int32:
            case DataType::Int16:
            case DataType::Int8:
                return 0;
            case DataType::Float:
            case DataType::Double:
                return 0.0;
            case DataType::Bool:
                return false;
            case DataType::VarChar:
            case DataType::String:
                return '';
            case DataType::FloatVector:
                return [];
            case DataType::BinaryVector:
                return '';
            default:
                return null;
        }
    }

    public static function convertRecordsToFieldData(array $records): array
    {
        if (empty($records)) {
            return [];
        }

        $firstItem = reset($records);
        if (!is_array($firstItem)) {
            throw new ParamException('Records must be an array of associative arrays');
        }

        $fields = [];
        $fieldNames = array_keys($firstItem);

        foreach ($fieldNames as $fieldName) {
            $values = [];
            $dataType = null;

            foreach ($records as $row) {
                $value = $row[$fieldName] ?? null;
                $values[] = $value;
                
                if ($dataType === null) {
                    $dataType = self::inferDataType($value);
                }
            }

            $fields[] = self::buildFieldData($fieldName, $values, $dataType);
        }

        return $fields;
    }

    private static function inferDataType($value): int
    {
        if (is_int($value)) {
            return DataType::Int64;
        }
        if (is_float($value)) {
            return DataType::Float;
        }
        if (is_bool($value)) {
            return DataType::Bool;
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!is_float($v) && !is_int($v)) {
                    return DataType::VarChar;
                }
            }
            return DataType::FloatVector;
        }
        return DataType::VarChar;
    }

    /**
     * Extract values from a FieldData protobuf object.
     *
     * @param FieldData $field The field data to extract values from.
     * @return array The extracted values as a flat array.
     */
    public static function extractFieldValues(FieldData $field): array
    {
        $scalars = $field->getScalars();
        if ($scalars !== null) {
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
            if ($scalars->getBytesData() !== null) {
                return iterator_to_array($scalars->getBytesData()->getData());
            }
            return [];
        }

        $vectors = $field->getVectors();
        if ($vectors !== null) {
            if ($vectors->getFloatVector() !== null) {
                $flat = iterator_to_array($vectors->getFloatVector()->getData());
                $dim = $vectors->getDim();
                if ($dim > 0) {
                    return array_chunk($flat, (int)$dim);
                }
                return $flat;
            }
            if ($vectors->getSparseFloatVector() !== null) {
                return self::extractSparseFloatVectors($vectors);
            }
            if ($vectors->getBinaryVector() !== null) {
                return [$vectors->getBinaryVector()];
            }
            if ($vectors->getFloat16Vector() !== null) {
                return [$vectors->getFloat16Vector()];
            }
            if ($vectors->getBfloat16Vector() !== null) {
                return [$vectors->getBfloat16Vector()];
            }
            return [];
        }

        return [];
    }

    /**
     * Convert an array of FieldData objects into row-oriented associative arrays.
     *
     * @param FieldData[] $fieldsData Array of FieldData objects.
     * @return array<int, array<string, mixed>> Rows of data as associative arrays.
     */
    public static function fieldDataToRows(array $fieldsData): array
    {
        if (empty($fieldsData)) {
            return [];
        }

        // Determine row count from the first non-empty field
        $rowCount = 0;
        foreach ($fieldsData as $field) {
            $values = self::extractFieldValues($field);
            $cnt = count($values);
            if ($cnt > $rowCount) {
                $rowCount = $cnt;
            }
        }

        if ($rowCount === 0) {
            return [];
        }

        $result = [];
        foreach ($fieldsData as $field) {
            $fieldName = $field->getFieldName();
            $values = self::extractFieldValues($field);
            for ($i = 0; $i < $rowCount && $i < count($values); $i++) {
                $result[$i][$fieldName] = $values[$i];
            }
            // Pad missing rows for this field with null
            for ($i = count($values); $i < $rowCount; $i++) {
                $result[$i][$fieldName] = null;
            }
        }

        return $result;
    }

    /**
     * Extract sparse float vectors from a VectorField.
     *
     * Sparse vectors are stored as packed bytes in contents.
     * Each element is a serialized SparseFloatArray with contents entries.
     *
     * @param VectorField $vectors The vector field containing sparse vectors.
     * @return array<int, array<int, float>> Array of sparse vectors (each is [dim => value]).
     */
    private static function extractSparseFloatVectors(VectorField $vectors): array
    {
        $sparse = $vectors->getSparseFloatVector();
        if ($sparse === null) {
            return [];
        }

        $result = [];
        $contents = $sparse->getContents();
        if ($contents === null) {
            return [];
        }

        foreach ($contents as $content) {
            // Sparse vector is serialized as: [num_pairs][dim1:value1][dim2:value2]...
            // Using unpack with proper format
            $packed = $content;
            $numPairs = unpack('V', substr($packed, 0, 4))[1];
            $vec = [];
            $offset = 4;
            for ($j = 0; $j < $numPairs; $j++) {
                if ($offset + 8 > strlen($packed)) {
                    break;
                }
                $dim = unpack('V', substr($packed, $offset, 4))[1];
                $value = unpack('f', substr($packed, $offset + 4, 4))[1];
                $vec[$dim] = $value;
                $offset += 8;
            }
            $result[] = $vec;
        }

        return $result;
    }
}