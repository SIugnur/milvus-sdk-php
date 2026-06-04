<?php
namespace Milvus\SDK\Helpers;

use Milvus\Proto\Common\KeyValuePair;
use Milvus\Proto\Schema\CollectionSchema;
use Milvus\Proto\Schema\FieldSchema;
use Milvus\Proto\Schema\DataType;
use Milvus\Proto\Schema\FunctionSchema;
use Milvus\Proto\Schema\FunctionType;
use Milvus\SDK\Exceptions\ParamException;

class SchemaHelper
{
    public static function buildCollectionSchema(string $name, array $fields, string $description = '', bool $enableDynamicField = false, array $functions = []): CollectionSchema
    {
        $schema = new CollectionSchema();
        $schema->setName($name);
        $schema->setDescription($description);
        $schema->setEnableDynamicField($enableDynamicField);

        $fieldObjs = [];
        foreach ($fields as $i => $field) {
            $fieldObjs[] = self::buildFieldSchema($field, $i + 1);
        }
        $schema->setFields($fieldObjs);

        $functionObjs = [];
        foreach ($functions as $i => $function) {
            $functionObjs[] = self::buildFunctionSchema($function, $i + 1);
        }
        $schema->setFunctions($functionObjs);

        return $schema;
    }

    public static function buildFieldSchema(array $field, int $fieldId = 1): FieldSchema
    {
        if (!isset($field['name'])) {
            throw new ParamException('Field name is required');
        }
        if (!isset($field['data_type'])) {
            throw new ParamException('Field data_type is required');
        }

        $fs = new FieldSchema();
        $fs->setFieldID($fieldId);
        $fs->setName($field['name']);
        $fs->setIsPrimaryKey($field['is_primary_key'] ?? false);
        $fs->setAutoID($field['autoID'] ?? false);
        $fs->setIsPartitionKey($field['is_partition_key'] ?? false);
        $fs->setDataType($field['data_type']);

        if (isset($field['description'])) {
            $fs->setDescription($field['description']);
        }

        if (isset($field['type_params']) && is_array($field['type_params'])) {
            $params = [];
            foreach ($field['type_params'] as $k => $v) {
                $kv = new KeyValuePair();
                $kv->setKey($k);
                $kv->setValue((string)$v);
                $params[] = $kv;
            }
            $fs->setTypeParams($params);
        }

        if (isset($field['default_value'])) {
            $fs->setDefaultValue($field['default_value']);
        }

        if (isset($field['nullable'])) {
            $fs->setNullable($field['nullable']);
        }

        if (isset($field['element_type'])) {
            $fs->setElementType($field['element_type']);
        }

        return $fs;
    }

    public static function buildKeyValuePair(string $key, string $value): KeyValuePair
    {
        $kv = new KeyValuePair();
        $kv->setKey($key);
        $kv->setValue($value);
        return $kv;
    }

    public static function buildKeyValuePairs(array $params): array
    {
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = self::buildKeyValuePair($key, (string)$value);
        }
        return $pairs;
    }

    public static function buildFunctionSchema(array $function, int $functionId = 1): FunctionSchema
    {
        if (!isset($function['name'])) {
            throw new ParamException('Function name is required');
        }
        if (!isset($function['type'])) {
            throw new ParamException('Function type is required');
        }

        $fs = new FunctionSchema();
        $fs->setId($functionId);
        $fs->setName($function['name']);
        $fs->setType($function['type']);

        if (isset($function['description'])) {
            $fs->setDescription($function['description']);
        }

        if (isset($function['input_field_names']) && is_array($function['input_field_names'])) {
            $fs->setInputFieldNames($function['input_field_names']);
        }

        if (isset($function['input_field_ids']) && is_array($function['input_field_ids'])) {
            $fs->setInputFieldIds($function['input_field_ids']);
        }

        if (isset($function['output_field_names']) && is_array($function['output_field_names'])) {
            $fs->setOutputFieldNames($function['output_field_names']);
        }

        if (isset($function['output_field_ids']) && is_array($function['output_field_ids'])) {
            $fs->setOutputFieldIds($function['output_field_ids']);
        }

        if (isset($function['params']) && is_array($function['params'])) {
            $fs->setParams(self::buildKeyValuePairs($function['params']));
        }

        return $fs;
    }
}