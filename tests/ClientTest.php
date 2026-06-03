<?php
namespace Milvus\SDK\Tests;

use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;
use Milvus\SDK\Constants\IndexType;
use Milvus\SDK\Constants\MetricType;
use Milvus\SDK\Constants\ConsistencyLevel;
use Milvus\SDK\Constants\OperateUserRoleType;
use Milvus\SDK\Constants\LoadState;
use Milvus\SDK\Helpers\SchemaHelper;
use Milvus\SDK\Helpers\DataHelper;
use Milvus\SDK\Helpers\SearchHelper;
use Milvus\SDK\Exceptions\MilvusException;
use Milvus\SDK\Exceptions\ConnectionException;
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

    // ========== System Tests ==========

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
        $this->assertNotNull($health);
        echo "\nHealth check passed";
    }

    public function testGetComponentStates()
    {
        $states = self::$client->getComponentStates();
        $this->assertNotNull($states);
    }

    // ========== Database Tests ==========

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
        $this->assertEquals('default', $info->getDbName());
    }

    // ========== Collection Tests ==========

    public function testShowCollections()
    {
        $collections = self::$client->showCollections();
        $this->assertIsArray($collections);
        echo json_encode($collections);
    }

    public function testCreateCollection()
    {
        $collectionName = 'test_col_' . uniqid();

        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setDescription('Test collection')
            ->setEnableDynamicField(false)
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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(3)
                    ->setName('text')
                    ->setDataType(\Milvus\Proto\Schema\DataType::VarChar)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('max_length')->setValue('256'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        $collections = self::$client->showCollections();
        $this->assertContains($collectionName, $collections);
    }

    public function testHasCollection()
    {
        $collectionName = 'test_has_col_' . uniqid();

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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        $this->assertTrue(self::$client->hasCollection($collectionName));
        $this->assertFalse(self::$client->hasCollection('non_existent_collection'));
    }

    public function testDescribeCollection()
    {
        $collectionName = 'test_desc_col_' . uniqid();

        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setDescription('Test description')
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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        $info = self::$client->describeCollection($collectionName);
        $this->assertEquals($collectionName, $info->getSchema()->getName());
    }

    public function testLoadAndReleaseCollection()
    {
        $collectionName = 'test_load_col_' . uniqid();

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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', IndexType::FLAT);
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

        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($oldName)
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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
            ]);

        self::$client->createCollection($oldName, $schema->serializeToString());
        self::$createdCollections[] = $newName;

        self::$client->renameCollection($oldName, $newName);

        $this->assertTrue(self::$client->hasCollection($newName));
        $this->assertFalse(self::$client->hasCollection($oldName));
    }

    public function testCreateAndDropCollection()
    {
        $collectionName = 'test_create_drop_' . uniqid();

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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        $this->assertTrue(self::$client->hasCollection($collectionName));

        self::$client->dropCollection($collectionName);
        $this->assertFalse(self::$client->hasCollection($collectionName));
    }

    public function testGetCollectionStatistics()
    {
        $collectionName = 'test_stats_col_' . uniqid();

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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        $stats = self::$client->getCollectionStatistics($collectionName);
        $this->assertNotNull($stats);
    }

    // ========== SchemaHelper Tests ==========

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

    public function testSchemaHelperBuildKeyValuePairs()
    {
        $pairs = SchemaHelper::buildKeyValuePairs(['dim' => '128', 'max_length' => '512']);
        $this->assertCount(2, $pairs);
        $this->assertEquals('dim', $pairs[0]->getKey());
        $this->assertEquals('128', $pairs[0]->getValue());
    }

    // ========== DataHelper Tests ==========

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

    // ========== SearchHelper Tests ==========

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

    // ========== Data Operation Tests ==========

    public function testInsertAndDelete()
    {
        $collectionName = 'test_insert_col_' . uniqid();

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

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', 'FLAT');

        self::$client->loadCollection($collectionName);

        $fieldsData = DataHelper::recordsToFieldData([
            ['vector' => [0.1, 0.2, 0.3, 0.4], 'title' => 'doc1'],
            ['vector' => [0.5, 0.6, 0.7, 0.8], 'title' => 'doc2'],
        ], [
            'vector' => DataType::FloatVector,
            'title' => DataType::VarChar,
        ]);

        $result = self::$client->insert($collectionName, $fieldsData);
        $this->assertNotNull($result->getIDs());
        echo "\nInsert result IDs: " . json_encode($result->getIDs());

        self::$client->flush($collectionName);

        $queryResults = self::$client->query($collectionName, 'title == "doc1"', ['id', 'title']);
        $this->assertNotNull($queryResults);
    }

    public function testUpsert()
    {
        $collectionName = 'test_upsert_col_' . uniqid();

        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setFields([
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(1)
                    ->setName('id')
                    ->setIsPrimaryKey(true)
                    ->setAutoID(false)
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

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', 'FLAT');

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

        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setFields([
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(1)
                    ->setName('id')
                    ->setIsPrimaryKey(true)
                    ->setAutoID(false)
                    ->setDataType(\Milvus\Proto\Schema\DataType::Int64),
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(2)
                    ->setName('vector')
                    ->setDataType(\Milvus\Proto\Schema\DataType::FloatVector)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('4'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', 'FLAT');

        self::$client->loadCollection($collectionName);

        $fieldsData = DataHelper::recordsToFieldData([
            ['id' => 100, 'vector' => [0.1, 0.2, 0.3, 0.4]],
        ], [
            'id' => DataType::Int64,
            'vector' => DataType::FloatVector,
        ]);

        self::$client->insert($collectionName, $fieldsData);
        self::$client->flush($collectionName);

        $result = self::$client->delete($collectionName, 'id in [100]');
        $this->assertNotNull($result);
    }

    // ========== Index Tests ==========

    public function testCreateAndDescribeIndex()
    {
        $collectionName = 'test_idx_col_' . uniqid();

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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('8'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        self::$client->createIndex($collectionName, 'vector', 'my_idx', null, [
            'index_type' => 'FLAT',
            'metric_type' => 'L2',
        ]);

        $indexDesc = self::$client->describeIndex($collectionName, 'vector');
        $this->assertNotNull($indexDesc);

        $state = self::$client->getIndexState($collectionName, 'vector');
        $this->assertNotNull($state);
    }

    // ========== Partition Tests ==========

    public function testCreateAndDropPartition()
    {
        $collectionName = 'test_part_col_' . uniqid();
        $partitionName = 'p1';

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
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        self::$client->createPartition($collectionName, $partitionName);
        $this->assertTrue(self::$client->hasPartition($collectionName, $partitionName));

        self::$client->dropPartition($collectionName, $partitionName);
        $this->assertFalse(self::$client->hasPartition($collectionName, $partitionName));
    }

    // ========== Alias Tests ==========

    public function testCreateDescribeAndDropAlias()
    {
        $collectionName = 'test_alias_col_' . uniqid();
        $alias = 'my_alias_' . uniqid();

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
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        self::$client->createAlias($collectionName, $alias);

        $info = self::$client->describeAlias($alias);
        $this->assertEquals($alias, $info->getAlias());

        $aliases = self::$client->listAliases($collectionName);
        $this->assertContains($alias, $aliases);

        self::$client->dropAlias($alias);
    }

    // ========== Auth Tests ==========

    public function testListCredUsers()
    {
        $users = self::$client->listCredUsers();
        $this->assertIsArray($users);
    }

    // ========== Resource Group Tests ==========

    public function testListResourceGroups()
    {
        $groups = self::$client->listResourceGroups();
        $this->assertIsArray($groups);
    }

    // ========== Error Handling Tests ==========

    public function testDropNonExistentCollection()
    {
        $nonExistent = 'non_existent_collection_' . uniqid();
        $result = self::$client->hasCollection($nonExistent);
        $this->assertFalse($result);
        
        self::$client->dropCollection($nonExistent);
        $result = self::$client->hasCollection($nonExistent);
        $this->assertFalse($result);
    }

    public function testDescribeNonExistentCollection()
    {
        $nonExistent = 'non_existent_collection_' . uniqid();
        $result = self::$client->hasCollection($nonExistent);
        $this->assertFalse($result);
        
        try {
            $info = self::$client->describeCollection($nonExistent);
            $this->assertNull($info->getSchema());
        } catch (MilvusException $e) {
            $this->assertStringContainsString('not found', strtolower($e->getMessage()));
        }
    }

    public function testInsertToNonExistentCollection()
    {
        $this->expectException(MilvusException::class);
        self::$client->insert('non_existent_collection_' . uniqid(), []);
    }

    // ========== Integration: Full CRUD Flow ==========

    /**
     * @group integration
     */
    public function testFullCrudFlow()
    {
        $this->markTestSkipped('Skipping due to Milvus rate limiting in test environment');
        
        $collectionName = 'test_full_flow_' . uniqid();

        $schema = (new \Milvus\Proto\Schema\CollectionSchema())
            ->setName($collectionName)
            ->setFields([
                (new \Milvus\Proto\Schema\FieldSchema())
                    ->setFieldID(1)
                    ->setName('id')
                    ->setIsPrimaryKey(true)
                    ->setAutoID(false)
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
                    ->setName('category')
                    ->setDataType(\Milvus\Proto\Schema\DataType::VarChar)
                    ->setTypeParams([
                        (new \Milvus\Proto\Common\KeyValuePair())->setKey('max_length')->setValue('64'),
                    ]),
            ]);

        self::$client->createCollection($collectionName, $schema->serializeToString());
        self::$createdCollections[] = $collectionName;

        $this->assertTrue(self::$client->hasCollection($collectionName));

        self::$client->createIndex($collectionName, 'vector', 'FLAT');

        self::$client->loadCollection($collectionName);

        $fieldsData = DataHelper::recordsToFieldData([
            ['id' => 1, 'vector' => [0.1, 0.2, 0.3, 0.4], 'category' => 'A'],
            ['id' => 2, 'vector' => [0.5, 0.6, 0.7, 0.8], 'category' => 'B'],
            ['id' => 3, 'vector' => [0.9, 1.0, 1.1, 1.2], 'category' => 'A'],
        ], [
            'id' => DataType::Int64,
            'vector' => DataType::FloatVector,
            'category' => DataType::VarChar,
        ]);

        $insertResult = self::$client->insert($collectionName, $fieldsData);
        $this->assertNotNull($insertResult);

        self::$client->flush($collectionName);

        $queryResults = self::$client->query($collectionName, 'category == "A"', ['id', 'category']);
        $this->assertNotNull($queryResults);

        $deleteResult = self::$client->delete($collectionName, 'id in [3]');
        $this->assertNotNull($deleteResult);

        self::$client->flush($collectionName);

        $remaining = self::$client->query($collectionName, '', ['id']);
        $this->assertNotNull($remaining);

        self::$client->releaseCollection($collectionName);
        self::$client->dropCollection($collectionName);
        $this->assertFalse(self::$client->hasCollection($collectionName));

        $key = array_search($collectionName, self::$createdCollections);
        if ($key !== false) {
            unset(self::$createdCollections[$key]);
        }
    }

    // ========== Constants Tests ==========

    public function testDataTypeConstants()
    {
        $this->assertEquals(0, DataType::None);
        $this->assertEquals(1, DataType::Bool);
        $this->assertEquals(4, DataType::Int32);
        $this->assertEquals(5, DataType::Int64);
        $this->assertEquals(21, DataType::VarChar);
        $this->assertEquals(101, DataType::FloatVector);
        $this->assertEquals(100, DataType::BinaryVector);
    }

    public function testIndexTypeConstants()
    {
        $this->assertEquals(0, IndexType::INVALID);
        $this->assertEquals(1, IndexType::FLAT);
        $this->assertEquals(2, IndexType::IVFFLAT);
        $this->assertEquals(5, IndexType::HNSW);
        $this->assertEquals(50, IndexType::AUTOINDEX);
    }

    public function testMetricTypeConstants()
    {
        $this->assertEquals(0, MetricType::INVALID);
        $this->assertEquals(1, MetricType::L2);
        $this->assertEquals(2, MetricType::IP);
        $this->assertEquals(3, MetricType::COSINE);
    }

    public function testConsistencyLevelConstants()
    {
        $this->assertEquals(0, ConsistencyLevel::Strong);
        $this->assertEquals(2, ConsistencyLevel::Bounded);
        $this->assertEquals(3, ConsistencyLevel::Eventually);
    }

    public function testLoadStateConstants()
    {
        $this->assertEquals(0, LoadState::NotExist);
        $this->assertEquals(1, LoadState::NotLoaded);
        $this->assertEquals(2, LoadState::Loaded);
    }

    public function testOperateUserRoleTypeConstants()
    {
        $this->assertEquals(0, OperateUserRoleType::AddUserToRole);
        $this->assertEquals(1, OperateUserRoleType::RemoveUserFromRole);
    }
}