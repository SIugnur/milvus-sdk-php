<?php
namespace Milvus\SDK\Tests;

use Milvus\Proto\Schema\FunctionType;
use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;
use PHPUnit\Framework\TestCase;

class SimpleExampleTest extends TestCase
{
    private static Client $client;

    public static function setUpBeforeClass(): void
    {
        $host = getenv('MILVUS_HOST') ?: 'localhost';
        $port = (int)(getenv('MILVUS_PORT') ?: 19530);

        self::$client = new Client([
            'host' => $host,
            'port' => $port,
            'timeout' => 30,
            'token' => 'root:Milvus',
        ]);
    }

    public function testSimpleDbCollectionInsertQuery()
    {
        $dbName = 'test_simple_db_' . uniqid();
        $collectionName = 'test_simple_col_' . uniqid();

        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        self::$client->createCollection(
            name: $collectionName,
            fields: [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ],
            dbName: $dbName,
        );
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        self::$client->createIndex($collectionName, 'vector', $dbName, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName, $dbName);

        // 使用新写法：直接传入关联数组
        $insertResult = self::$client->insert($collectionName, [
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'doc1'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'title' => 'doc2'],
            ['vector' => [0.9, 1.0, 1.1, 1.2], 'title' => 'doc3'],
        ], $dbName);
        $this->assertNotNull($insertResult);
        $this->assertNotNull($insertResult->getInsertIds());

        self::$client->flush($collectionName, $dbName);
        $queryResults = self::$client->query($collectionName, 'title == "doc2"', ['id', 'title'], $dbName);
        $this->assertNotNull($queryResults);
        $rows = $queryResults->toArray();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('title', $rows[0]);
        $this->assertEquals('doc2', $rows[0]['title']);

        self::$client->releaseCollection($collectionName, $dbName);
        self::$client->dropCollection($collectionName, $dbName);
        $this->assertFalse(self::$client->hasCollection($collectionName, $dbName));

        self::$client->dropDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertNotContains($dbName, $dbs);
    }

    public function testSearch()
    {
        $dbName = 'test_search_db_' . uniqid();
        $collectionName = 'test_search_col_' . uniqid();

        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        self::$client->createCollection(
            name: $collectionName,
            fields: [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ],
            dbName: $dbName
        );
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        self::$client->createIndex($collectionName, 'vector', $dbName, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName, $dbName);

        // 使用新写法：直接传入关联数组
        $insertResult = self::$client->insert($collectionName, [
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'doc1'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'title' => 'doc2'],
            ['vector' => [0.9, 1.0, 1.1, 1.2], 'title' => 'doc3'],
        ], $dbName);
        $this->assertNotNull($insertResult);

        self::$client->flush($collectionName, $dbName);

        $searchResult = self::$client->search(
            $collectionName,
            [[0.1, 0.2, 0.3, 0.4]],
            'vector',
            3,
            ['nprobe' => 10],
            ['title'],
            '',
            $dbName
        );
        echo json_encode($searchResult->toArray());
        $this->assertNotNull($searchResult);
        $this->assertGreaterThan(0, $searchResult->getNumQueries());
        $this->assertGreaterThan(0, $searchResult->getTopK());
        $this->assertNotEmpty($searchResult->getIds());
        $this->assertNotEmpty($searchResult->getScores());

        $rows = $searchResult->toArray();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('score', $rows[0]);
        $this->assertArrayHasKey('title', $rows[0]);

        self::$client->releaseCollection($collectionName, $dbName);
        self::$client->dropCollection($collectionName, $dbName);
        $this->assertFalse(self::$client->hasCollection($collectionName, $dbName));

        self::$client->dropDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertNotContains($dbName, $dbs);
    }

    public function testHybridSearch()
    {
        $dbName = 'test_hybrid_db_' . uniqid();
        $collectionName = 'test_hybrid_col_' . uniqid();

        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => [
                    'max_length' => 512,
                    'enable_analyzer' => true,
                    'multi_analyzer_params' => json_encode([
                        'analyzers' => [
                            'English' => [
                                'type' => 'english'
                            ],
                            'Mandarin' => [
                                'type' => 'chinese'
                            ],
                            'default' => [
                                'type' => 'standard'
                            ]
                        ],
                        'by_field' => 'language'
                    ]),
                ]],
                ['name' => 'title_sparse', 'data_type' => DataType::SparseFloatVector],
                ['name' => 'language', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]]
            ],
            '',
            false,
            [
                [
                    'name' => 'title_bm25_emb',
                    'type' => FunctionType::BM25,
                    'input_field_names' => ['title'],
                    'output_field_names' => ['title_sparse']
                ]
            ],
            $dbName
        );
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        self::$client->createIndex($collectionName, 'vector', $dbName, ['index_type' => 'FLAT']);
        self::$client->createIndex($collectionName, 'title_sparse', $dbName, [
            'metric_type' => 'BM25',
            'index_type' => 'SPARSE_INVERTED_INDEX',
            'params' => json_encode([
                'inverted_index_algo' => 'DAAT_MAXSCORE',
                'bm25_k1' => 1.2,
                'bm25_b' => 0.75
            ])
        ]);
        self::$client->loadCollection($collectionName, $dbName);

        // 使用新写法：直接传入关联数组
        $insertResult = self::$client->insert($collectionName, [
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'language' => 'English', 'title' => 'document one: If you prefer to use a custom analyzer name or need to maintain compatibility with the existing configuration, you can use the mapping parameter. This will create an alias for your analyzer - both the original detection engine name and the custom name can be used.'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'language' => 'Mandarin', 'title' => 'document two: 如果喜欢使用自定义分析器名称，或需要保持与现有配置的兼容性，可以使用 mapping 参数。这将为您的分析器创建别名--原始检测引擎名称和自定义名称均可使用。'],
            ['vector' => [0.9, 1.0, 1.1, 1.2], 'language' => 'default', 'title' => 'document three: 사용자 정의 분석기 이름을 좋아하거나 기존 구성과 호환성을 유지하려면 매핑 인수를 사용할 수 있다.그러면 분석기의 별명을 만들 수 있습니다-원시 탐지 엔진 이름과 사용자 정의 이름 모두 가능합니다.'],
        ], $dbName);
        $this->assertNotNull($insertResult);

        self::$client->flush($collectionName, $dbName);

        $hybridResult = self::$client->hybridSearch(
            $collectionName,
            [
                [
                    'vectors' => [[0.2, 0.3, 0.4, 0.5]],
                    'annsField' => 'vector',
                    'topK' => 3,
                    'params' => ['nprobe' => 10],
                    'outputFields' => ['title']
                ],
                [
                    'vectors' => ['什么是自定义分析器？'],
                    'annsField' => 'title_sparse',
                    'topK' => 3,
                    'searchParams' => ['analyzer_name' => 'Mandarin'],
                    'outputFields' => ['title']
                ]
            ],
            [
                'strategy' => 'rrf',
                'params' => json_encode([
                    'functions' => [
                        [
                            'name' => 'rrf',
                            'type' => 'Rerank',
                            'inputFieldNames' => [],
                            'params' => ['reranker' => 'rrf', 'k' => 60]
                        ]
                    ]
                ]),
                'limit' => '10'
            ],
            ['title'],
            $dbName
        );
        echo json_encode($hybridResult->toArray());

        $this->assertNotNull($hybridResult);
        $this->assertGreaterThan(0, $hybridResult->getNumQueries());
        $this->assertNotEmpty($hybridResult->getIds());
        $this->assertNotEmpty($hybridResult->getScores());

        $rows = $hybridResult->toArray();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('score', $rows[0]);

        self::$client->releaseCollection($collectionName, $dbName);
        self::$client->dropCollection($collectionName, $dbName);
        $this->assertFalse(self::$client->hasCollection($collectionName, $dbName));

        self::$client->dropDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertNotContains($dbName, $dbs);
    }

    public function testRunAnalyzer()
    {
        $dbName = 'test_analyzer_db_' . uniqid();
        $collectionName = 'test_analyzer_col_' . uniqid();

        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'text', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ],
            '',
            false,
            [],
            $dbName
        );
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        $result = self::$client->runAnalyzer(
            'document two: 如果喜欢使用自定义分析器名称，或需要保持与现有配置的兼容性，可以使用 mapping 参数。这将为您的分析器创建别名--原始检测引擎名称和自定义名称均可使用。',
            ['type' => 'standard'],
            true,
            null,
            null,
            $dbName
        );
        $this->assertNotNull($result);

        foreach ($result->getResults()[0] as $token) {
            print_r($token['token'] . "\n");
        }

        $tokens = $result->getResults();
        $this->assertNotEmpty($tokens);
        $this->assertNotEmpty($tokens[0]);
        $this->assertArrayHasKey('token', $tokens[0][0]);
        $this->assertArrayHasKey('start_offset', $tokens[0][0]);

        self::$client->dropCollection($collectionName, $dbName);
        $this->assertFalse(self::$client->hasCollection($collectionName, $dbName));

        self::$client->dropDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertNotContains($dbName, $dbs);
    }

    public function testDynamicFieldInsertAndQuery()
    {
        $dbName = 'test_dynamic_db_' . uniqid();
        $collectionName = 'test_dynamic_col_' . uniqid();

        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        self::$client->createCollection(
            name: $collectionName,
            fields: [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ],
            description: 'Collection with dynamic fields enabled',
            enableDynamicField: true,
            dbName: $dbName,
        );
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        self::$client->createIndex($collectionName, 'vector', $dbName, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName, $dbName);

        $insertResult = self::$client->insert($collectionName, [
            [
                'vector' => [0.1, 0.2, 0.3, 0.4],
                'title' => 'doc1',
                'dynamic_int' => 100,
                'dynamic_string' => 'dynamic value 1',
                'dynamic_float' => 3.14,
            ],
            [
                'vector' => [0.5, 0.6, 0.7, 0.8],
                'title' => 'doc2',
                'dynamic_int' => 200,
                'dynamic_string' => 'dynamic value 2',
                'dynamic_bool' => true,
            ],
            [
                'vector' => [0.9, 1.0, 1.1, 1.2],
                'title' => 'doc3',
                'dynamic_int' => 300,
            ],
        ], $dbName);
        $this->assertNotNull($insertResult);
        $this->assertNotNull($insertResult->getInsertIds());

        self::$client->flush($collectionName, $dbName);

        $queryResults = self::$client->query($collectionName, 'title == "doc2"', ['id', 'title', 'dynamic_int', 'dynamic_string', 'dynamic_bool'], $dbName);
        $this->assertNotNull($queryResults);
        $rows = $queryResults->toArray();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('title', $rows[0]);
        $this->assertArrayHasKey('dynamic_int', $rows[0]);
        $this->assertArrayHasKey('dynamic_string', $rows[0]);
        $this->assertArrayHasKey('dynamic_bool', $rows[0]);
        $this->assertEquals('doc2', $rows[0]['title']);
        $this->assertEquals(200, $rows[0]['dynamic_int']);
        $this->assertEquals('dynamic value 2', $rows[0]['dynamic_string']);
        $this->assertEquals(true, $rows[0]['dynamic_bool']);

        $queryResults = self::$client->query($collectionName, 'dynamic_int > 150', ['id', 'title', 'dynamic_int'], $dbName);
        $this->assertNotNull($queryResults);
        $rows = $queryResults->toArray();
        $this->assertNotEmpty($rows);
        $this->assertGreaterThanOrEqual(2, count($rows));

        self::$client->releaseCollection($collectionName, $dbName);
        self::$client->dropCollection($collectionName, $dbName);
        $this->assertFalse(self::$client->hasCollection($collectionName, $dbName));

        self::$client->dropDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertNotContains($dbName, $dbs);
    }

    public function test_deleteAll()
    {
        $listDatabases = self::$client->listDatabases();
        foreach ($listDatabases as $listDatabase) {
            if (str_starts_with($listDatabase, 'test_')) {
                $showCollections = self::$client->showCollections($listDatabase);
                foreach ($showCollections as $showCollection) {
                    self::$client->dropCollection($showCollection, $listDatabase);
                }
                self::$client->dropDatabase($listDatabase);
            }
        }
    }
}
