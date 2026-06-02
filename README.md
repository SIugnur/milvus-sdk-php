# Milvus PHP SDK

PHP SDK for [Milvus](https://github.com/milvus-io/milvus) vector database. Provides a gRPC client for vector similarity search, metadata filtering, and full collection/index/user management.

**Package**: `siugnur/milvus-sdk-php` | **PHP**: ^8.0 | **License**: Apache-2.0

## Installation

```bash
composer require siugnur/milvus-sdk-php
```

### Requirements

- PHP 8.0 or higher
- [gRPC PHP extension](https://github.com/grpc/grpc/tree/master/src/php) (`ext-grpc`) — required by the underlying `grpc/grpc` library
- `ext-protobuf` (optional) — for faster protobuf serialization

Install the required extensions:

```bash
pecl install grpc
pecl install protobuf   # optional, but recommended for performance
```

> **Note**: `ext-grpc` is required by the `grpc/grpc` Composer library. When you run `composer require siugnur/milvus-sdk-php`, Composer will check for it automatically.

## Quick Start

### Connect to Milvus

```php
use Milvus\SDK\Client;

// Local Milvus (insecure)
$client = new Client([
    'host' => 'localhost',
    'port' => 19530,
]);

// With authentication
$client = new Client([
    'host' => 'localhost',
    'port' => 19530,
    'username' => 'root',
    'password' => 'milvus',
]);

// With API token (Zilliz Cloud)
$client = new Client([
    'host' => 'your-endpoint.zillizcloud.com',
    'port' => 19530,
    'token' => 'your-api-key',
    'ssl' => true,
]);

// Custom database
$client = new Client([
    'host' => 'localhost',
    'port' => 19530,
    'database' => 'custom_db',
]);
```

### Create Collection, Insert, and Search

```php
use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;
use Milvus\SDK\Constants\MetricType;
use Milvus\SDK\Constants\IndexType;

$client = new Client(['host' => 'localhost', 'port' => 19530]);

// 1. Create collection with schema
$schema = (new \Milvus\Proto\Schema\CollectionSchema())
    ->setName('my_collection')
    ->setDescription('My test collection')
    ->setAutoID(true)
    ->setEnableDynamicField(true)
    ->setFields([
        (new \Milvus\Proto\Schema\FieldSchema())
            ->setFieldID(1)
            ->setName('id')
            ->setIsPrimaryKey(true)
            ->setAutoID(true)
            ->setDataType(DataType::Int64),
        (new \Milvus\Proto\Schema\FieldSchema())
            ->setFieldID(2)
            ->setName('vector')
            ->setDataType(DataType::FloatVector)
            ->setTypeParams([
                (new \Milvus\Proto\Common\KeyValuePair())->setKey('dim')->setValue('128'),
            ]),
        (new \Milvus\Proto\Schema\FieldSchema())
            ->setFieldID(3)
            ->setName('text')
            ->setDataType(DataType::VarChar)
            ->setTypeParams([
                (new \Milvus\Proto\Common\KeyValuePair())->setKey('max_length')->setValue('512'),
            ]),
    ]);

$client->createCollection('my_collection', $schema->serializeToString());

// 2. Create index
$client->createIndex('my_collection', 'vector', 'vector_idx', null, [
    'index_type' => IndexType::IVFFLAT,
    'metric_type' => MetricType::L2,
    'params' => json_encode(['nlist' => 128]),
]);

// 3. Load collection into memory
$client->loadCollection('my_collection');

// 4. Insert data
use Milvus\SDK\Helpers\DataHelper;

$fieldsData = DataHelper::recordsToFieldData([
    ['vector' => array_fill(0, 128, 0.1), 'text' => 'document 1'],
    ['vector' => array_fill(0, 128, 0.2), 'text' => 'document 2'],
], [
    'vector' => DataType::FloatVector,
    'text' => DataType::VarChar,
]);

$result = $client->insert('my_collection', $fieldsData);
echo "Inserted IDs: " . json_encode($result->getIDs()) . "\n";

// 5. Search
use Milvus\SDK\Helpers\SearchHelper;

$searchReq = SearchHelper::buildSearchRequest(
    'my_collection',
    [array_fill(0, 128, 0.15)],
    'vector',
    10,
    ['nprobe' => 16],
    ['text']
);

$results = $client->search($searchReq);
print_r($results->getResults());
```

## Client Configuration

```php
$client = new Client([
    'host'        => 'localhost',       // Milvus server host
    'port'        => 19530,             // Milvus server port
    'token'       => null,              // API token (Zilliz Cloud)
    'username'    => null,              // Username for auth
    'password'    => null,              // Password for auth
    'ssl'         => false,             // Enable SSL/TLS
    'database'    => 'default',         // Default database name
    'timeout'     => 30,                // Request timeout in seconds
    'max_retries' => 3,                 // Max retry attempts
    'retry_delay' => 10,               // Retry delay in milliseconds
]);
```

## API Reference

### System Operations

```php
$version = $client->getVersion();                         // Server version
$health = $client->checkHealth();                         // Health check
$states = $client->getComponentStates();                  // Component states
$metrics = $client->getMetrics('{"metric_key":"*"}');     // System metrics
$response = $client->connect();                           // Connect
```

### Database Operations

```php
$client->createDatabase('my_db');                         // Create database
$client->dropDatabase('my_db');                           // Drop database
$databases = $client->listDatabases();                    // List all databases
$info = $client->describeDatabase('my_db');               // Describe database
$client->alterDatabase('my_db');                          // Alter database
```

### Collection Operations

```php
$client->createCollection('name', $schemaString);         // Create collection
$client->dropCollection('name');                          // Drop collection
$exists = $client->hasCollection('name');                 // Check if exists
$info = $client->describeCollection('name');              // Describe schema
$client->loadCollection('name');                          // Load into memory
$client->releaseCollection('name');                       // Release from memory
$collections = $client->showCollections();                // List collections
$client->renameCollection('old_name', 'new_name');        // Rename collection
$client->truncateCollection('name');                      // Clear all data
$client->alterCollection('name', $properties);            // Alter properties
$stats = $client->getCollectionStatistics('name');        // Get statistics
```

### Index Operations

```php
$client->createIndex('collection', 'field', 'idx_name', null, [
    'index_type' => 'IVFFLAT',
    'metric_type' => 'L2',
    'params' => '{"nlist": 128}',
]);                                                        // Create index
$info = $client->describeIndex('collection', 'field');     // Describe index
$state = $client->getIndexState('collection', 'field');    // Get index state
$progress = $client->getIndexBuildProgress('collection', 'field'); // Build progress
$stats = $client->getIndexStatistics('collection');        // Index statistics
$client->dropIndex('collection', 'field');                 // Drop index
```

### Partition Operations

```php
$client->createPartition('collection', 'partition_name');  // Create partition
$client->dropPartition('collection', 'partition_name');    // Drop partition
$exists = $client->hasPartition('collection', 'name');     // Check if exists
$partitions = $client->showPartitions('collection');       // List partitions
$client->loadPartitions('collection', ['p1', 'p2']);       // Load partitions
$client->releasePartitions('collection', ['p1']);          // Release partitions
```

### Data Operations

#### insert

```php
use Milvus\SDK\Helpers\DataHelper;

$fieldsData = DataHelper::recordsToFieldData($records, $schema);
$result = $client->insert('collection', $fieldsData);
// Returns: MutationResult with IDs, succ_index, err_index
```

#### upsert

```php
$result = $client->upsert('collection', $fieldsData);
```

#### delete

```php
// By expression
$result = $client->delete('collection', "id in [1, 2, 3]");
// With partition
$result = $client->delete('collection', "age > 18", 'default', 'my_partition');
```

#### search

```php
use Milvus\SDK\Helpers\SearchHelper;

$req = SearchHelper::buildSearchRequest(
    collectionName: 'my_collection',
    vectors: [[0.1, 0.2, ...]],     // Query vector(s)
    annsField: 'vector',             // Vector field name
    topK: 10,                        // Number of results
    params: ['nprobe' => 16],        // Index-specific params
    outputFields: ['text'],          // Fields to return
    filter: 'text like "doc%"',      // Optional filter
    dbName: ''                       // Optional database name
);
$results = $client->search($req);
```

#### query

```php
$results = $client->query('collection', 'id > 10', ['id', 'text']);
```

### Flush Operations

```php
$result = $client->flush('collection');                    // Flush collection
$client->flushAll();                                       // Flush all collections
$flushed = $client->getFlushState([1, 2, 3]);              // Check flush state
```

### Alias Operations

```php
$client->createAlias('collection', 'my_alias');            // Create alias
$client->dropAlias('my_alias');                            // Drop alias
$client->alterAlias('collection', 'my_alias');             // Alter alias
$info = $client->describeAlias('my_alias');                // Describe alias
$aliases = $client->listAliases('collection');             // List aliases
```

### User & Role Management (RBAC)

```php
// Users
$client->createCredential('username', 'password');
$client->updateCredential('username', 'old_pw', 'new_pw');
$client->deleteCredential('username');
$users = $client->listCredUsers();

// Roles
$client->createRole('admin');
$client->dropRole('admin');
$client->operateUserRole('username', 'admin', \Milvus\SDK\Constants\OperateUserRoleType::AddUserToRole);

// Query
$roles = $client->selectRole('admin');
$users = $client->selectUser('username');
```

### Resource Group Operations

```php
$client->createResourceGroup('group1');
$client->dropResourceGroup('group1');
$groups = $client->listResourceGroups();
$info = $client->describeResourceGroup('group1');
$client->transferReplica('source', 'target', 'collection', 2);
```

### Import Operations

```php
$result = $client->import('collection', ['data.json']);
$state = $client->getImportState(12345);
$tasks = $client->listImportTasks('collection', 10);
```

### Compaction Operations

```php
$result = $client->manualCompaction(12345);
$state = $client->getCompactionState(12345);
```

### Segment Operations

```php
$info = $client->getPersistentSegmentInfo('collection');
$info = $client->getQuerySegmentInfo('collection');
```

## Using SchemaHelper

```php
use Milvus\SDK\Helpers\SchemaHelper;
use Milvus\SDK\Constants\DataType;

$schema = SchemaHelper::buildCollectionSchema(
    'my_collection',
    [
        ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
        ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
        ['name' => 'text', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 512]],
    ],
    'Collection description',
    true
);

$client->createCollection('my_collection', $schema->serializeToString());
```

## Using DataHelper

```php
use Milvus\SDK\Helpers\DataHelper;
use Milvus\SDK\Constants\DataType;

$fieldsData = DataHelper::buildFieldData('vector', [[0.1, 0.2], [0.3, 0.4]], DataType::FloatVector);

$fieldsData = DataHelper::recordsToFieldData(
    [
        ['id' => 1, 'text' => 'hello'],
        ['id' => 2, 'text' => 'world'],
    ],
    ['id' => DataType::Int64, 'text' => DataType::VarChar]
);
```

## Using SearchHelper

```php
use Milvus\SDK\Helpers\SearchHelper;

$searchReq = SearchHelper::buildSearchRequest(
    'my_collection', [$queryVector], 'vector', 10,
    ['nprobe' => 16], ['id', 'text'], 'id > 0'
);
$results = $client->search($searchReq);

$queryReq = SearchHelper::buildQueryRequest('my_collection', 'id > 10', ['id', 'text']);
```

## Advanced Examples

### Hybrid Search

```php
$denseSearch = SearchHelper::buildSearchRequest('my_collection', [$denseVector], 'dense_field', 30, ['nprobe' => 20]);
$sparseSearch = SearchHelper::buildSearchRequest('my_collection', ['search text'], 'sparse_field', 30, ['analyzer_name' => 'chinese']);

$hybridReq = (new \Milvus\Proto\Milvus\HybridSearchRequest())
    ->setCollectionName('my_collection')
    ->setRequests([$denseSearch, $sparseSearch])
    ->setOutputFields(['id', 'title'])
    ->setRankParams([
        (new \Milvus\Proto\Common\KeyValuePair())->setKey('strategy')->setValue('rrf'),
        (new \Milvus\Proto\Common\KeyValuePair())->setKey('limit')->setValue('30'),
    ]);

$results = $client->hybridSearch($hybridReq);
```

## Error Handling

```php
use Milvus\SDK\Exceptions\MilvusException;
use Milvus\SDK\Exceptions\ConnectionException;

try {
    $client->loadCollection('non_existent');
} catch (ConnectionException $e) {
    echo "Connection error: " . $e->getMessage();
} catch (MilvusException $e) {
    echo "Milvus error (code {$e->getStatusCode()}): " . $e->getMessage();
}
```

## Running Tests

```bash
# Run all tests (requires a running Milvus instance)
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-html ./coverage

# Specify custom Milvus host/port
MILVUS_HOST=192.168.1.100 MILVUS_PORT=19530 ./vendor/bin/phpunit
```

## License

Apache-2.0