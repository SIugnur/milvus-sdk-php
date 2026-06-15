<?php
namespace Milvus\SDK\Helpers;

use Milvus\Proto\Common\PlaceholderGroup;
use Milvus\Proto\Common\PlaceholderValue;
use Milvus\Proto\Common\PlaceholderType;
use Milvus\Proto\Milvus\SearchRequest;
use Milvus\Proto\Milvus\QueryRequest;
use Milvus\SDK\Exceptions\ParamException;

class SearchHelper
{
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
    ): SearchRequest {
        $placeholderValues = [];
        foreach ($data as $i => $value) {
            $pv = new PlaceholderValue();
            $pv->setTag('$' . $i);
            if (is_array($value) && isset($value[0]) && is_numeric($value[0])) {
                $pv->setType(PlaceholderType::FloatVector);
                $pv->setValues([pack('f*', ...$value)]);
            } elseif (is_string($value)) {
                $pv->setType(PlaceholderType::VarChar);
                $pv->setValues([$value]);
            } else {
                throw new ParamException('Unsupported vector type');
            }
            $placeholderValues[] = $pv;
        }

        $pg = new PlaceholderGroup();
        $pg->setPlaceholders($placeholderValues);

        $mergedParams = [
            'anns_field' => $annsField,
            'topk' => (string)$topK,
            'params' => json_encode((object)$params),
        ];
        if ($searchParams !== null) {
            $mergedParams = array_merge($mergedParams, $searchParams);
        }
        $searchParams = Helper::toKeyValuePairs($mergedParams);

        $req = new SearchRequest();
        $req->setCollectionName($collectionName);
        $req->setDslType(\Milvus\Proto\Common\DslType::Dsl);
        $req->setPlaceholderGroup($pg->serializeToString());
        $req->setSearchParams($searchParams);
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
}