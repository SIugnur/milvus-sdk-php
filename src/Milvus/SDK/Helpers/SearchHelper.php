<?php
namespace Milvus\SDK\Helpers;

use Milvus\Proto\Common\PlaceholderGroup;
use Milvus\Proto\Common\PlaceholderValue;
use Milvus\Proto\Common\PlaceholderType;
use Milvus\Proto\Common\KeyValuePair;
use Milvus\Proto\Milvus\SearchRequest;
use Milvus\Proto\Milvus\HybridSearchRequest;
use Milvus\Proto\Milvus\QueryRequest;
use Milvus\SDK\Exceptions\ParamException;

class SearchHelper
{
    public static function buildSearchRequest(
        string $collectionName,
        array $vectors,
        string $annsField,
        int $topK = 100,
        array $params = [],
        array $outputFields = [],
        string $filter = '',
        string $dbName = '',
    ): SearchRequest {
        $placeholderValues = [];
        foreach ($vectors as $i => $vector) {
            $pv = new PlaceholderValue();
            $pv->setTag('$' . $i);
            if (is_array($vector) && isset($vector[0]) && is_numeric($vector[0])) {
                $pv->setType(PlaceholderType::FloatVector);
                $pv->setValues([pack('f*', ...$vector)]);
            } elseif (is_string($vector)) {
                $pv->setType(PlaceholderType::VarChar);
                $pv->setValues([$vector]);
            } elseif (is_array($vector) && self::isAssocArray($vector)) {
                $pv->setType(PlaceholderType::SparseFloatVector);
                $buf = '';
                foreach ($vector as $idx => $val) {
                    $buf .= pack('Vf', $idx, $val);
                }
                $pv->setValues([$buf]);
            } else {
                throw new ParamException('Unsupported vector type');
            }
            $placeholderValues[] = $pv;
        }

        $pg = new PlaceholderGroup();
        $pg->setPlaceholders($placeholderValues);

        $searchParams = [new KeyValuePair(['key' => 'anns_field', 'value' => $annsField])];
        $searchParams[] = new KeyValuePair(['key' => 'topk', 'value' => (string)$topK]);
        $searchParams[] = new KeyValuePair(['key' => 'params', 'value' => json_encode((object)$params)]);

        $req = new SearchRequest();
        $req->setCollectionName($collectionName);
        $req->setDslType(\Milvus\Proto\Common\DslType::Dsl);
        $req->setPlaceholderGroup($pg->serializeToString());
        $req->setSearchParams($searchParams);
        $req->setNq(count($vectors));

        if ($dbName) {
            $req->setDbName($dbName);
        }
        if ($outputFields) {
            $req->setOutputFields($outputFields);
        }
        if ($filter) {
            $req->setDsl($filter);
        }

        return $req;
    }

    private static function isAssocArray(array $array): bool
    {
        if ([] === $array) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
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
}