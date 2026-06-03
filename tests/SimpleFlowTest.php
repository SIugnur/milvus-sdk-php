<?php
namespace Milvus\SDK\Tests;

use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;
use Milvus\SDK\Helpers\DataHelper;
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
        echo json_encode($queryResults->toArray());
        $this->assertNotNull($queryResults);

        // 6. Clean up
        self::$client->releaseCollection($collectionName, $dbName);
        self::$client->dropCollection($collectionName, $dbName);
        $this->assertFalse(self::$client->hasCollection($collectionName, $dbName));

        self::$client->dropDatabase($dbName);
        $dbs = self::$client->listDatabases();
        $this->assertNotContains($dbName, $dbs);
    }
}
