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
        ?array $searchParams = null,
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
            } else {
                throw new ParamException('Unsupported vector type');
            }
            $placeholderValues[] = $pv;
        }

        $pg = new PlaceholderGroup();
        $pg->setPlaceholders($placeholderValues);

        $searchParamsInternal = [new KeyValuePair(['key' => 'anns_field', 'value' => $annsField])];
        $searchParamsInternal[] = new KeyValuePair(['key' => 'topk', 'value' => (string)$topK]);
        $searchParamsInternal[] = new KeyValuePair(['key' => 'params', 'value' => json_encode((object)$params)]);
        if ($searchParams !== null) {
            foreach ($searchParams as $k => $v) {
                $searchParamsInternal[] = new KeyValuePair(['key' => (string)$k, 'value' => (string)$v]);
            }
        }
        $searchParams = $searchParamsInternal;

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