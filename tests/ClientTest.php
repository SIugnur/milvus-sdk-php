<?php
namespace Milvus\SDK\Tests;

use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;
use Milvus\SDK\Constants\LoadState;
use Milvus\SDK\Helpers\Helper;
use Milvus\SDK\Helpers\SchemaHelper;
use Milvus\SDK\Helpers\DataHelper;
use Milvus\SDK\Helpers\SearchHelper;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private static Client $client;
    private static string $testDbName = 'php_sdk_test_db';
    private static array $createdCollections = [];

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

    protected function setUp(): void
    {
        self::$client->setDefaultDatabase('default');
    }

    protected function tearDown(): void
    {
        foreach (self::$createdCollections as $name) {
            try {
                self::$client->releaseCollection($name);
            } catch (\Exception $e) {
            }
            try {
                self::$client->dropCollection($name);
            } catch (\Exception $e) {
            }
        }
        self::$createdCollections = [];

        try {
            self::$client->dropDatabase(self::$testDbName);
        } catch (\Exception $e) {
        }
    }

    public function testConstructorWithArrayConfig()
    {
        $c = new Client(['host' => 'localhost', 'port' => 19530]);
        $this->assertInstanceOf(Client::class, $c);
    }

    public function testConstructorWithDefaultConfig()
    {
        $c = new Client();
        $this->assertInstanceOf(Client::class, $c);
    }

    public function testGetVersion()
    {
        $version = self::$client->getVersion();
        $this->assertNotEmpty($version);
        $this->assertIsString($version);
        echo "\nMilvus version: " . $version;
    }

    public function testCheckHealth()
    {
        $health = self::$client->checkHealth();
        $this->assertTrue($health->isHealthy());
        echo "\nHealth check passed";
    }

    public function testGetComponentStates()
    {
        $states = self::$client->getComponentStates();
        $this->assertNotNull($states);
    }

    public function testListDatabases()
    {
        $databases = self::$client->listDatabases();
        $this->assertIsArray($databases);
        $this->assertContains('default', $databases);
    }

    public function testCreateDatabase()
    {
        self::$client->createDatabase(self::$testDbName);
        $databases = self::$client->listDatabases();
        $this->assertContains(self::$testDbName, $databases);
    }

    public function testCreateAndDropDatabase()
    {
        $dbName = 'test_db_' . uniqid();

        self::$client->createDatabase($dbName);
        $databases = self::$client->listDatabases();
        $this->assertContains($dbName, $databases);

        self::$client->dropDatabase($dbName);
        $databases = self::$client->listDatabases();
        $this->assertNotContains($dbName, $databases);
    }

    public function testDescribeDatabase()
    {
        $info = self::$client->describeDatabase('default');
        $this->assertNotNull($info);
        $this->assertEquals('default', $info->getName());
    }

    public function testShowCollections()
    {
        $collections = self::$client->showCollections();
        $this->assertIsArray($collections);
        echo json_encode($collections);
    }

    public function testCreateCollection()
    {
        $collectionName = 'test_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
                ['name' => 'text', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ],
            'Test collection',
            false
        );
        self::$createdCollections[] = $collectionName;

        $collections = self::$client->showCollections();
        $this->assertContains($collectionName, $collections);
    }

    public function testHasCollection()
    {
        $collectionName = 'test_has_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        $this->assertTrue(self::$client->hasCollection($collectionName));
        $this->assertFalse(self::$client->hasCollection('non_existent_collection'));
    }

    public function testDescribeCollection()
    {
        $collectionName = 'test_desc_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
            ],
            'Test description'
        );
        self::$createdCollections[] = $collectionName;

        $info = self::$client->describeCollection($collectionName);
        $this->assertEquals($collectionName, $info->getName());
    }

    public function testLoadAndReleaseCollection()
    {
        $collectionName = 'test_load_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', null, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName);
        $state = self::$client->getLoadState($collectionName);
        $this->assertEquals(LoadState::Loaded, $state);

        self::$client->releaseCollection($collectionName);
        $state = self::$client->getLoadState($collectionName);
        $this->assertEquals(LoadState::NotLoaded, $state);
    }

    public function testRenameCollection()
    {
        $oldName = 'test_rename_old_' . uniqid();
        $newName = 'test_rename_new_' . uniqid();

        self::$client->createCollection(
            $oldName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
            ]
        );
        self::$createdCollections[] = $newName;

        self::$client->renameCollection($oldName, $newName);

        $this->assertTrue(self::$client->hasCollection($newName));
        $this->assertFalse(self::$client->hasCollection($oldName));
    }

    public function testCreateAndDropCollection()
    {
        $collectionName = 'test_create_drop_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
            ]
        );
        $this->assertTrue(self::$client->hasCollection($collectionName));

        self::$client->dropCollection($collectionName);
        $this->assertFalse(self::$client->hasCollection($collectionName));
    }

    public function testGetCollectionStatistics()
    {
        $collectionName = 'test_stats_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        $stats = self::$client->getCollectionStatistics($collectionName);
        $this->assertNotNull($stats);
    }

    public function testSchemaHelperBuildCollectionSchema()
    {
        $schema = SchemaHelper::buildCollectionSchema(
            'test_schema',
            [
                [
                    'name' => 'id',
                    'data_type' => DataType::Int64,
                    'is_primary_key' => true,
                    'autoID' => true,
                ],
                [
                    'name' => 'vector',
                    'data_type' => DataType::FloatVector,
                    'type_params' => ['dim' => 128],
                ],
                [
                    'name' => 'title',
                    'data_type' => DataType::VarChar,
                    'type_params' => ['max_length' => 256],
                ],
            ],
            'Test schema',
            true
        );

        $this->assertEquals('test_schema', $schema->getName());
        $this->assertEquals('Test schema', $schema->getDescription());
        $this->assertTrue($schema->getEnableDynamicField());

        $fields = $schema->getFields();
        $this->assertCount(3, $fields);
        $this->assertEquals('id', $fields[0]->getName());
        $this->assertEquals('vector', $fields[1]->getName());
        $this->assertEquals('title', $fields[2]->getName());
    }

    public function testHelperToKeyValuePairs()
    {
        $pairs = Helper::toKeyValuePairs(['dim' => '128', 'max_length' => '512']);
        $this->assertCount(2, $pairs);
        $this->assertEquals('dim', $pairs[0]->getKey());
        $this->assertEquals('128', $pairs[0]->getValue());
    }

    public function testDataHelperBuildFieldDataInt64()
    {
        $fd = DataHelper::buildFieldData('id', [1, 2, 3], DataType::Int64);
        $this->assertEquals('id', $fd->getFieldName());
        $this->assertEquals(DataType::Int64, $fd->getType());
    }

    public function testDataHelperBuildFieldDataVarChar()
    {
        $fd = DataHelper::buildFieldData('text', ['a', 'b', 'c'], DataType::VarChar);
        $this->assertEquals('text', $fd->getFieldName());
        $this->assertEquals(DataType::VarChar, $fd->getType());
    }

    public function testDataHelperBuildFieldDataFloatVector()
    {
        $fd = DataHelper::buildFieldData('vector', [[0.1, 0.2], [0.3, 0.4]], DataType::FloatVector);
        $this->assertEquals('vector', $fd->getFieldName());
        $this->assertEquals(DataType::FloatVector, $fd->getType());
    }

    public function testDataHelperRecordsToFieldData()
    {
        $records = [
            ['id' => 1, 'title' => 'hello'],
            ['id' => 2, 'title' => 'world'],
        ];
        $schemaDef = [
            'id' => DataType::Int64,
            'title' => DataType::VarChar,
        ];

        $fields = DataHelper::recordsToFieldData($records, $schemaDef);
        $this->assertCount(2, $fields);
        $this->assertEquals('id', $fields[0]->getFieldName());
        $this->assertEquals('title', $fields[1]->getFieldName());
    }

    public function testSearchHelperBuildSearchRequest()
    {
        $req = SearchHelper::buildSearchRequest(
            'my_collection',
            [[0.1, 0.2, 0.3]],
            'vector',
            10,
            ['nprobe' => 16],
            ['id', 'text'],
            'id > 0'
        );

        $this->assertEquals('my_collection', $req->getCollectionName());
        $this->assertEquals(1, $req->getNq());
    }

    public function testSearchHelperBuildQueryRequest()
    {
        $req = SearchHelper::buildQueryRequest(
            'my_collection',
            'id > 10',
            ['id', 'text']
        );

        $this->assertEquals('my_collection', $req->getCollectionName());
        $this->assertEquals('id > 10', $req->getExpr());
    }

    public function testInsertAndDelete()
    {
        $collectionName = 'test_insert_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', null, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName);

        $fieldsData = DataHelper::recordsToFieldData([
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'doc1'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'title' => 'doc2'],
        ], [
            'vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        $result = self::$client->insert($collectionName, $fieldsData);
        $this->assertNotEmpty($result->getInsertIds());

        self::$client->flush($collectionName);

        $queryResults = self::$client->query($collectionName, 'title == "doc1"', ['id', 'title']);
        $this->assertNotNull($queryResults);
    }

    public function testUpsert()
    {
        $collectionName = 'test_upsert_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => false],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', null, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName);

        $fieldsData = DataHelper::recordsToFieldData([
            ['id' => 1, 'vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'original'],
        ], [
            'id' => DataType::Int64,
            'vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        $result = self::$client->upsert($collectionName, $fieldsData);
        $this->assertNotNull($result);
    }

    public function testDeleteByExpression()
    {
        $collectionName = 'test_delete_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => false],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', null, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName);

        $fieldsData = DataHelper::recordsToFieldData([
            ['id' => 1, 'vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'delete_me'],
        ], [
            'id' => DataType::Int64,
            'vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        self::$client->insert($collectionName, $fieldsData);
        self::$client->flush($collectionName);

        $result = self::$client->delete($collectionName, 'id == 1');
        $this->assertNotNull($result);
    }

    public function testSearch()
    {
        $collectionName = 'test_search_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'title', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 256]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', null, ['index_type' => 'FLAT']);
        self::$client->loadCollection($collectionName);

        $fieldsData = DataHelper::recordsToFieldData([
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'doc1'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'title' => 'doc2'],
        ], [
            'vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        self::$client->insert($collectionName, $fieldsData);
        self::$client->flush($collectionName);

        $results = self::$client->search(
            $collectionName,
            [[0.1, 0.2, 0.3, 0.4]],
            'vector',
            10,
            ['nprobe' => 10],
            ['title'],
            '',
            null
        );
        $this->assertNotNull($results);
        $this->assertGreaterThan(0, $results->getNumQueries());
    }

    public function testQueryWithLimitOffset()
    {
        $collectionName = 'test_query_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
                ['name' => 'value', 'data_type' => DataType::Int64],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', null, ['index_type' => 'FLAT']);

        $fieldsData = DataHelper::recordsToFieldData([
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'value' => 1],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'value' => 2],
            ['vector' => [0.9, 1.0, 1.1, 1.2], 'value' => 3],
        ], [
            'vector' => DataType::FloatVector,
            'value' => DataType::Int64,
        ]);

        self::$client->insert($collectionName, $fieldsData);
        self::$client->flush($collectionName);

        self::$client->loadCollection($collectionName);

        $results = self::$client->query($collectionName, 'value > 0', ['id', 'value'], null, 2, 0);
        $rows = $results->toArray();
        $this->assertCount(2, $rows);
    }

    public function testPartitionOperations()
    {
        $collectionName = 'test_partition_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createPartition($collectionName, 'p1');
        $this->assertTrue(self::$client->hasPartition($collectionName, 'p1'));

        $partitions = self::$client->showPartitions($collectionName);
        $this->assertNotNull($partitions);

        self::$client->dropPartition($collectionName, 'p1');
        $this->assertFalse(self::$client->hasPartition($collectionName, 'p1'));
    }

    public function testAliasOperations()
    {
        $collectionName = 'test_alias_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        $alias = 'test_alias_' . uniqid();

        self::$client->createAlias($collectionName, $alias);
        $aliases = self::$client->listAliases($collectionName);
        $this->assertContains($alias, $aliases);

        $info = self::$client->describeAlias($alias);
        $this->assertEquals($collectionName, $info->getCollectionName());

        self::$client->dropAlias($alias);
        $aliases = self::$client->listAliases($collectionName);
        $this->assertNotContains($alias, $aliases);
    }

    public function testIndexOperations()
    {
        $collectionName = 'test_index_col_' . uniqid();

        self::$client->createCollection(
            $collectionName,
            [
                ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
                ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 4]],
            ]
        );
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', null, ['index_type' => 'FLAT']);

        $info = self::$client->describeIndex($collectionName, 'vector');
        $this->assertNotNull($info);

        $state = self::$client->getIndexState($collectionName, 'vector');
        $this->assertNotNull($state);

        $progress = self::$client->getIndexBuildProgress($collectionName, 'vector');
        $this->assertNotNull($progress);

        self::$client->dropIndex($collectionName, 'vector');
    }
}