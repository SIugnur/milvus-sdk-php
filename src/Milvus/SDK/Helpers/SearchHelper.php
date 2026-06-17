<?php
namespace Milvus\SDK\Helpers;

use Milvus\Proto\Common\PlaceholderGroup;
use Milvus\Proto\Common\PlaceholderValue;
use Milvus\Proto\Common\PlaceholderType;
use Milvus\Proto\Milvus\SearchRequest;
use Milvus\Proto\Milvus\QueryRequest;
use Milvus\Proto\Schema\TemplateValue;
use Milvus\Proto\Schema\TemplateArrayValue;
use Milvus\Proto\Schema\BoolArray;
use Milvus\Proto\Schema\LongArray;
use Milvus\Proto\Schema\DoubleArray;
use Milvus\Proto\Schema\StringArray;
use Milvus\Proto\Schema\JSONArray;
use Milvus\Proto\Schema\TemplateArrayValueArray;
use Milvus\SDK\Exceptions\ParamException;

class SearchHelper
{
    /**
     * Build a SearchRequest protobuf message.
     *
     * @param string $collectionName
     * @param array $data Query data. Format depends on $placeholderType:
     *                    - FloatVector (default): [[0.1, 0.2, ...], ...]
     *                    - BinaryVector: ["\x00\x01...", ...]
     *                    - Float16Vector: ["\x00\x01...", ...]
     *                    - BFloat16Vector: ["\x00\x01...", ...]
     *                    - SparseFloatVector: [[0=>0.1, 5=>0.2], ...]
     *                    - Int8Vector: [[1, 2, 3], ...]
     *                    - VarChar: ["text query", ...]
     * @param string $annsField
     * @param int $topK
     * @param array $params Search parameters (e.g. ['nprobe' => 16]), JSON-encoded into the 'params' key
     * @param array $outputFields
     * @param string $filter
     * @param string $dbName
     * @param array|null $searchParams Additional raw key-value pairs merged into search_params
     * @param int|null $placeholderType PlaceholderType enum. If null, auto-detected from data.
     * @param int $offset Offset for paginated results
     * @param string|null $groupByField Group results by this field
     * @param bool|null $strictGroupSize Whether to enforce strict group size
     * @param int|null $groupSize Number of results per group
     * @param bool $ignoreGrowing Whether to ignore growing segments
     * @param string|null $hints Query hints
     * @param int|null $roundDecimal Score precision (number of decimal places)
     * @param array|null $exprValues Expression template values (associative array)
     * @return SearchRequest
     * @throws ParamException
     */
    public static function buildSearchRequest(
        string $collectionName,
        array $data,
        string $annsField,
        int $topK = 100,
        array $params = [],
        array $outputFields = [],
        string $filter = '',
        string $dbName = '',
        ?array $searchParams = null,
        ?int $placeholderType = null,
        int $offset = 0,
        ?string $groupByField = null,
        ?bool $strictGroupSize = null,
        ?int $groupSize = null,
        bool $ignoreGrowing = false,
        ?string $hints = null,
        ?int $roundDecimal = null,
        ?array $exprValues = null,
    ): SearchRequest {
        // Auto-detect placeholder type if not specified
        if ($placeholderType === null) {
            $placeholderType = self::detectPlaceholderType($data);
        }

        // Build placeholder group for vector search
        $placeholderValues = [];
        foreach ($data as $i => $value) {
            $pv = new PlaceholderValue();
            $pv->setTag('$' . $i);
            $pv->setType($placeholderType);

            switch ($placeholderType) {
                case PlaceholderType::FloatVector:
                    if (!is_array($value)) {
                        throw new ParamException('FloatVector data must be an array of floats');
                    }
                    $pv->setValues([pack('f*', ...$value)]);
                    break;

                case PlaceholderType::BinaryVector:
                    if (!is_string($value)) {
                        throw new ParamException('BinaryVector data must be a binary string');
                    }
                    $pv->setValues([$value]);
                    break;

                case PlaceholderType::Float16Vector:
                case PlaceholderType::BFloat16Vector:
                    if (!is_string($value)) {
                        throw new ParamException('Float16Vector/BFloat16Vector data must be a binary string');
                    }
                    $pv->setValues([$value]);
                    break;

                case PlaceholderType::Int8Vector:
                    if (!is_array($value)) {
                        throw new ParamException('Int8Vector data must be an array of integers');
                    }
                    $pv->setValues([pack('c*', ...$value)]);
                    break;

                case PlaceholderType::SparseFloatVector:
                    if (!is_array($value)) {
                        throw new ParamException('SparseFloatVector data must be an array of [index => value] pairs');
                    }
                    $buf = '';
                    foreach ($value as $idx => $val) {
                        $buf .= pack('Vf', $idx, (float) $val);
                    }
                    $pv->setValues([$buf]);
                    break;

                case PlaceholderType::VarChar:
                    if (!is_string($value)) {
                        throw new ParamException('VarChar data must be a string');
                    }
                    $pv->setValues([$value]);
                    break;

                default:
                    throw new ParamException("Unsupported placeholder type: $placeholderType");
            }

            $placeholderValues[] = $pv;
        }

        $pg = new PlaceholderGroup();
        $pg->setPlaceholders($placeholderValues);

        // Build search_params KeyValuePairs
        $mergedParams = [
            'anns_field' => $annsField,
            'topk' => (string)$topK,
            'params' => json_encode((object)$params),
        ];

        // Offset
        if ($offset > 0) {
            $mergedParams['offset'] = (string)$offset;
        }

        // Group by
        if ($groupByField !== null) {
            $mergedParams['group_by_field'] = $groupByField;
        }
        if ($strictGroupSize !== null) {
            $mergedParams['strict_group_size'] = $strictGroupSize ? 'true' : 'false';
        }
        if ($groupSize !== null) {
            $mergedParams['group_size'] = (string)$groupSize;
        }

        // Ignore growing
        if ($ignoreGrowing) {
            $mergedParams['ignore_growing'] = 'true';
        }

        // Hints
        if ($hints !== null) {
            $mergedParams['hints'] = $hints;
        }

        // Round decimal
        if ($roundDecimal !== null) {
            $mergedParams['round_decimal'] = (string)$roundDecimal;
        }

        // Merge additional raw search params (overrides defaults)
        if ($searchParams !== null) {
            $mergedParams = array_merge($mergedParams, $searchParams);
        }
        $searchParamsKv = Helper::toKeyValuePairs($mergedParams);

        $req = new SearchRequest();
        $req->setCollectionName($collectionName);
        $req->setDslType(\Milvus\Proto\Common\DslType::Dsl);
        $req->setPlaceholderGroup($pg->serializeToString());
        $req->setSearchParams($searchParamsKv);
        $req->setNq(count($data));

        if ($dbName) {
            $req->setDbName($dbName);
        }
        if ($outputFields) {
            $req->setOutputFields($outputFields);
        }
        if ($filter) {
            $req->setDsl($filter);
        }

        // Expression template values
        if ($exprValues !== null && !empty($exprValues)) {
            $req->setExprTemplateValues(self::buildExprTemplateValues($exprValues));
        }

        return $req;
    }

    public static function buildQueryRequest(
        string $collectionName,
        string $expr,
        array $outputFields = [],
        string $dbName = '',
    ): QueryRequest {
        $req = new QueryRequest();
        $req->setCollectionName($collectionName);
        $req->setExpr($expr);

        if ($dbName) {
            $req->setDbName($dbName);
        }
        if ($outputFields) {
            $req->setOutputFields($outputFields);
        }

        return $req;
    }

    /**
     * Convert a PHP associative array to protobuf TemplateValue map.
     *
     * @param array $exprValues Associative array of expression template values
     * @return array<string, TemplateValue>
     */
    public static function buildExprTemplateValues(array $exprValues): array
    {
        $result = [];
        foreach ($exprValues as $key => $value) {
            $tv = new TemplateValue();
            if (is_array($value)) {
                $tav = self::convertToTemplateArrayValue($value);
                $tv->setArrayVal($tav);
            } elseif (is_bool($value)) {
                $tv->setBoolVal($value);
            } elseif (is_int($value)) {
                $tv->setInt64Val($value);
            } elseif (is_float($value)) {
                $tv->setFloatVal($value);
            } elseif (is_string($value)) {
                $tv->setStringVal($value);
            } else {
                throw new ParamException("Unsupported expr template value type for key '$key'");
            }
            $result[$key] = $tv;
        }
        return $result;
    }

    /**
     * Convert a PHP array to a TemplateArrayValue protobuf message.
     */
    private static function convertToTemplateArrayValue(array $arr): TemplateArrayValue
    {
        $tav = new TemplateArrayValue();
        if (empty($arr)) {
            throw new ParamException('Expression template array value cannot be empty');
        }

        $first = reset($arr);

        if (is_bool($first)) {
            $ba = new BoolArray();
            $ba->setData($arr);
            $tav->setBoolData($ba);
        } elseif (is_int($first)) {
            $la = new LongArray();
            $la->setData($arr);
            $tav->setLongData($la);
        } elseif (is_float($first)) {
            $da = new DoubleArray();
            $da->setData($arr);
            $tav->setDoubleData($da);
        } elseif (is_string($first)) {
            $sa = new StringArray();
            $sa->setData($arr);
            $tav->setStringData($sa);
        } elseif (is_array($first)) {
            // Nested array
            $nested = [];
            foreach ($arr as $item) {
                $nested[] = self::convertToTemplateArrayValue($item);
            }
            $tava = new TemplateArrayValueArray();
            $tava->setData($nested);
            $tav->setArrayData($tava);
        } else {
            // Treat as JSON
            $jsonStrings = array_map(function ($v) {
                return is_array($v) ? json_encode($v) : (string) $v;
            }, $arr);
            $ja = new JSONArray();
            $ja->setData($jsonStrings);
            $tav->setJsonData($ja);
        }

        return $tav;
    }

    /**
     * Auto-detect PlaceholderType from the first element of data.
     *
     * @param array $data
     * @return int PlaceholderType enum value
     */
    private static function detectPlaceholderType(array $data): int
    {
        if (empty($data)) {
            return PlaceholderType::FloatVector;
        }

        $first = reset($data);

        if (is_string($first)) {
            return PlaceholderType::VarChar;
        }

        if (is_array($first)) {
            // Check if it's a sparse vector (associative array with non-sequential keys)
            if (!empty($first)) {
                $keys = array_keys($first);
                if ($keys !== range(0, count($first) - 1)) {
                    return PlaceholderType::SparseFloatVector;
                }
            }
            // Default: FloatVector
            return PlaceholderType::FloatVector;
        }

        return PlaceholderType::FloatVector;
    }
}
