<?php
namespace Milvus\SDK\Helpers;

use Milvus\Proto\Common\KeyValuePair;

class Helper
{
    public static function toKeyValuePairs(array $params): array
    {
        $result = [];
        foreach ($params as $k => $v) {
            $result[] = new KeyValuePair(['key' => (string)$k, 'value' => (string)$v]);
        }
        return $result;
    }
}
