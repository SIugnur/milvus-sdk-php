<?php
namespace Milvus\SDK\Tests;

use Milvus\Proto\Schema\FunctionSchema;
use Milvus\Proto\Schema\FunctionType;
use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;
use Milvus\SDK\Helpers\DataHelper;
use Milvus\SDK\Helpers\SearchHelper;
use Milvus\Proto\Common\KeyValuePair;
use Milvus\Proto\Milvus\HybridSearchRequest;
use Milvus\Proto\Milvus\RunAnalyzerRequest;
use PHPUnit\Framework\TestCase;

class SimpleFlowTest extends TestCase
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
        ]);
    }

    public function testSimpleDbCollectionInsertQuery()
    {
        $dbName = 'test_simple_db_' . uniqid();
        $collectionName = 'test_simple_col_' . uniqid();

        // 1. Create database
        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        // 2. Create collection with vector field
        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setFields([
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(1)
                    ->setName('id')
                    ->setIsPrimaryKey(true)
                    ->setAutoID(true)
                    ->setDataType(\Milvus\Proto\Schema\DataType::Int64),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(2)
                    ->setName('vector')
                    ->setDataType(\Milvus\Proto\Schema\DataType::FloatVector)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('4'),
                    ]),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(3)
                    ->setName('title')
                    ->setDataType(\Milvus\Proto\Schema\DataType::VarChar)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('max_length')->setValue('256'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString(), $dbName);
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        // 3. Create index and load collection
        self::$client->createIndex($collectionName, 'vector', 'FLAT', $dbName);
        self::$client->loadCollection($collectionName, $dbName);

        // 4. Insert data
        $fieldsData = DataHelper::recordsToFieldData([
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'doc1'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'title' => 'doc2'],
            ['vector' => [0.9, 1.0, 1.1, 1.2], 'title' => 'doc3'],
        ], [
            'vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        $insertResult = self::$client->insert($collectionName, $fieldsData, $dbName);
        $this->assertNotNull($insertResult);
        $this->assertNotNull($insertResult->getInsertIds());

        // 5. Flush and query data
        self::$client->flush($collectionName, $dbName);
        $queryResults = self::$client->query($collectionName, 'title == "doc2"', ['id', 'title'], $dbName);
        $this->assertNotNull($queryResults);
        $rows = $queryResults->toArray();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('title', $rows[0]);
        $this->assertEquals('doc2', $rows[0]['title']);

        // 6. Clean up
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

        // 1. Create database
        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        // 2. Create collection with vector field
        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setFields([
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(1)
                    ->setName('id')
                    ->setIsPrimaryKey(true)
                    ->setAutoID(true)
                    ->setDataType(\Milvus\Proto\Schema\DataType::Int64),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(2)
                    ->setName('vector')
                    ->setDataType(\Milvus\Proto\Schema\DataType::FloatVector)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('4'),
                    ]),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(3)
                    ->setName('title')
                    ->setDataType(\Milvus\Proto\Schema\DataType::VarChar)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('max_length')->setValue('256'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString(), $dbName);
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        // 3. Create index and load collection
        self::$client->createIndex($collectionName, 'vector', 'FLAT', $dbName);
        self::$client->loadCollection($collectionName, $dbName);

        // 4. Insert data
        $fieldsData = DataHelper::recordsToFieldData([
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'doc1'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'title' => 'doc2'],
            ['vector' => [0.9, 1.0, 1.1, 1.2], 'title' => 'doc3'],
        ], [
            'vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        $insertResult = self::$client->insert($collectionName, $fieldsData, $dbName);
        $this->assertNotNull($insertResult);
        $this->assertNotNull($insertResult->getInsertIds());

        // 5. Flush
        self::$client->flush($collectionName, $dbName);

        // 6. Search
        $searchRequest = SearchHelper::buildSearchRequest(
            $collectionName,
            [[0.1, 0.2, 0.3, 0.4]],
            'vector',
            3,
            ['nprobe' => 10],
            ['title'],
            '',
            $dbName
        );
        $searchResult = self::$client->search($searchRequest);
        echo json_encode($searchResult->toArray());
        $this->assertNotNull($searchResult);
        $this->assertGreaterThan(0, $searchResult->getNumQueries());
        $this->assertGreaterThan(0, $searchResult->getTopK());
        $ids = $searchResult->getIds();
        $this->assertNotEmpty($ids);
        $scores = $searchResult->getScores();
        $this->assertNotEmpty($scores);

        // Verify toArray() returns rows with id, score, and output fields
        $rows = $searchResult->toArray();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('score', $rows[0]);
        $this->assertArrayHasKey('title', $rows[0]);

        // 7. Clean up
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

        // 1. Create database
        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        // 2. Create collection with two vector fields (dense + sparse)
        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setFields([
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(1)
                    ->setName('id')
                    ->setIsPrimaryKey(true)
                    ->setAutoID(true)
                    ->setDataType(\Milvus\Proto\Schema\DataType::Int64),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(2)
                    ->setName('dense_vector')
                    ->setDataType(\Milvus\Proto\Schema\DataType::FloatVector)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('4'),
                    ]),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(3)
                    ->setName('sparse_vector')
                    ->setDataType(\Milvus\Proto\Schema\DataType::SparseFloatVector),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(4)
                    ->setName('title')
                    ->setDataType(\Milvus\Proto\Schema\DataType::VarChar)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('max_length')->setValue('256'),
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('enable_analyzer')->setValue(true),
                    ]),
            ])
            ->setFunctions([
                new FunctionSchema([
                    'name' => 'convert_sparse_vector_function',
                    'type' => FunctionType::BM25,
                    'input_field_names' => ['title'],
                    'output_field_names' => ['sparse_vector'],
                ])
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString(), $dbName);
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        // 3. Create indexes for both vector fields
        self::$client->createIndex($collectionName, 'dense_vector', 'FLAT', $dbName);
        self::$client->createIndex($collectionName, 'sparse_vector', 'SPARSE_INVERTED_INDEX', $dbName, ['metric_type' => 'BM25']);
        self::$client->loadCollection($collectionName, $dbName);

        // 4. Insert data
        $fieldsData = DataHelper::recordsToFieldData([
            [
                'dense_vector' => [0.1, 0.2, 0.3, 0.4],
                'title' => 'Who are you?',
            ],
            [
                'dense_vector' => [0.5, 0.6, 0.7, 0.8],
                'title' => 'doc2: I am fine.',
            ],
            [
                'dense_vector' => [0.9, 1.0, 1.1, 1.2],
                'title' => 'doc3: You are welcome. Thank you.',
            ],
        ], [
            'dense_vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        $insertResult = self::$client->insert($collectionName, $fieldsData, $dbName);
        $this->assertNotNull($insertResult);
        $this->assertNotNull($insertResult->getInsertIds());

        // 5. Flush
        self::$client->flush($collectionName, $dbName);

        // 6. Build sub-search requests for hybrid search
        $denseSearchReq = SearchHelper::buildSearchRequest(
            $collectionName,
            [[0.1, 0.2, 0.3, 0.4]],
            'dense_vector',
            3,
            ['nprobe' => 10],
            [],
            '',
            $dbName
        );

        $sparseSearchReq = SearchHelper::buildSearchRequest(
            $collectionName,
            ["find"],
            'sparse_vector',
            3,
            [],
            [],
            '',
            $dbName
        );

        // 7. Execute hybrid search
        $hybridReq = (new HybridSearchRequest())
            ->setCollectionName($collectionName)
            ->setDbName($dbName)
            ->setRequests([$sparseSearchReq])
            ->setRankParams([
                new KeyValuePair(['key' => 'rrf', 'value' => '{}']),
                new KeyValuePair(['key' => 'limit', 'value' => '10'])
            ])
            ->setOutputFields(['title']);

        $hybridResult = self::$client->hybridSearch($hybridReq);
        $this->assertNotNull($hybridResult);
        $this->assertGreaterThan(0, $hybridResult->getNumQueries());
        $ids = $hybridResult->getIds();
        $this->assertNotEmpty($ids);
        $scores = $hybridResult->getScores();
        $this->assertNotEmpty($scores);

        echo json_encode($hybridResult->toArray());

        // Verify toArray() returns rows with id, score, and output fields
        $rows = $hybridResult->toArray();
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('score', $rows[0]);
        $this->assertArrayHasKey('title', $rows[0]);

        // 8. Clean up
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

        // 1. Create database
        self::$client->createDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertContains($dbName, $dbs);

        // 2. Create collection with a varchar field
        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setFields([
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(1)
                    ->setName('id')
                    ->setIsPrimaryKey(true)
                    ->setAutoID(true)
                    ->setDataType(\Milvus\Proto\Schema\DataType::Int64),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(2)
                    ->setName('vector')
                    ->setDataType(\Milvus\Proto\Schema\DataType::FloatVector)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('4'),
                    ]),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(3)
                    ->setName('text')
                    ->setDataType(\Milvus\Proto\Schema\DataType::VarChar)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('max_length')->setValue('256'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString(), $dbName);
        $this->assertTrue(self::$client->hasCollection($collectionName, $dbName));

        // 3. Call runAnalyzer with a standard analyzer (standalone mode: analyzer params only, no collection/field)
        $request = (new RunAnalyzerRequest())
            ->setDbName($dbName)
            ->setAnalyzerParams(json_encode(['type' => 'chinese']))
            ->setPlaceholder(['2026年6月文具可以签领了，请已购买文具的同事留意到【前台】签领~'])
            ->setWithDetail(true);

        $result = self::$client->runAnalyzer($request);
        $this->assertNotNull($result);

        $tokens = $result->getResults();
        $this->assertNotEmpty($tokens);
        $this->assertNotEmpty($tokens[0]);
        $this->assertArrayHasKey('token', $tokens[0][0]);
        $this->assertArrayHasKey('start_offset', $tokens[0][0]);

        // 4. Clean up
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
