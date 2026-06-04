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

$client = new Client([
    'host'        => 'localhost',   // Milvus server host
    'port'        => 19530,         // Milvus server port
    'username'    => 'root',        // Optional: for authentication
    'password'    => 'milvus',      // Optional: for authentication
    'token'       => null,          // Optional: API token (Zilliz Cloud)
    'ssl'         => false,         // Enable SSL/TLS
    'database'    => 'default',     // Default database name
    'timeout'     => 30,            // Request timeout in seconds
    'max_retries' => 3,             // Max retry attempts
    'retry_delay' => 10,            // Retry delay in milliseconds
]);
```

### Create Collection, Insert, and Search

```php
use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;

$client = new Client(['host' => 'localhost', 'port' => 19530]);

// 1. Create collection with array-based schema (NO protobuf required!)
$client->createCollection(
    'my_collection',
    [
        ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
        ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
        ['name' => 'text', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 512]],
    ],
    'My test collection',
    true
);

// 2. Create index
$client->createIndex('my_collection', 'vector', null, [
    'index_type' => 'IVFFLAT',
    'metric_type' => 'L2',
    'params' => '{"nlist": 128}',
]);

// 3. Load collection into memory
$client->loadCollection('my_collection');

// 4. Insert data (directly pass associative arrays!)
$result = $client->insert('my_collection', [
    ['vector' => array_fill(0, 128, 0.1), 'text' => 'document 1'],
    ['vector' => array_fill(0, 128, 0.2), 'text' => 'document 2'],
]);
echo "Inserted IDs: " . json_encode($result->getInsertIds()) . "\n";

// 5. Search directly with simple parameters
$results = $client->search(
    'my_collection',
    [array_fill(0, 128, 0.15)],  // Query vectors
    'vector',                      // Vector field name
    10,                           // Top K
    ['nprobe' => 16],              // Search params
    ['text'],                      // Output fields
    '',                            // Filter expression
    null                           // Database name
);

print_r($results->toArray());
```

## API Reference

### Database Operations

```php
$client->createDatabase('my_db');
$client->dropDatabase('my_db');
$databases = $client->listDatabases();
$info = $client->describeDatabase('my_db');  // Returns DatabaseDescriptor
$client->alterDatabase('my_db');
```

### Collection Operations

```php
// Create collection with array-based schema
$client->createCollection(
    'my_collection',
    [
        ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
        ['name' => 'vec', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
    ],
    'Description',
    true,   // enableDynamicField
    'default',  // dbName (optional)
    1       // shardsNum (optional)
);

$client->dropCollection('my_collection');
$exists = $client->hasCollection('my_collection');
$info = $client->describeCollection('my_collection');  // Returns CollectionInfo
$client->loadCollection('my_collection');
$client->releaseCollection('my_collection');
$collections = $client->showCollections();
$client->renameCollection('old_name', 'new_name');
$client->truncateCollection('my_collection');
$client->alterCollection('my_collection', ['collection.ttl.seconds' => '3600']);
$stats = $client->getCollectionStatistics('my_collection');  // Returns CollectionStats
```

### Index Operations

```php
$client->createIndex('my_collection', 'vector', null, [
    'index_type' => 'IVFFLAT',
    'metric_type' => 'L2',
    'params' => '{"nlist": 128}',
]);

$info = $client->describeIndex('my_collection', 'vector');  // Returns IndexInfo
$state = $client->getIndexState('my_collection', 'vector');  // Returns IndexStateInfo
$progress = $client->getIndexBuildProgress('my_collection', 'vector');  // Returns IndexBuildProgress
$client->dropIndex('my_collection', 'vector');
```

### Data Operations

#### Insert

```php
// Directly pass associative arrays (data types are auto-inferred)
$result = $client->insert('my_collection', [
    ['vector' => [0.1, 0.2, 0.3], 'text' => 'doc1', 'value' => 100],
    ['vector' => [0.4, 0.5, 0.6], 'text' => 'doc2', 'value' => 200],
]);
$ids = $result->getInsertIds();

// With optional parameters
$result = $client->insert(
    'my_collection',
    [['vector' => [0.1, 0.2, 0.3], 'text' => 'doc1']],
    'default',       // dbName
    'my_partition',  // partitionName
    [1, 2, 3],      // hashKeys
    0,              // schemaTimestamp
    'my_namespace'  // namespace
);
```

#### Upsert

```php
// Basic upsert (auto-infer types)
$result = $client->upsert('my_collection', [
    ['id' => 1, 'vector' => [0.1, 0.2, 0.3], 'text' => 'updated'],
    ['id' => 2, 'vector' => [0.4, 0.5, 0.6], 'text' => 'new'],
]);

// Upsert with partial update (only update specified fields)
$result = $client->upsert(
    'my_collection',
    [['id' => 1, 'value' => 999]],  // Only update 'value' field
    'default',       // dbName
    '',              // partitionName
    [],              // hashKeys
    0,               // schemaTimestamp
    true,            // partialUpdate (incremental update)
    '',              // namespace
    []               // fieldOps
);

// Upsert with fieldOps (for array field operations)
use Milvus\Proto\Schema\FieldPartialUpdateOp;

$fieldOps = [
    (new FieldPartialUpdateOp())
        ->setFieldName('tags')
        ->setOp(\Milvus\Proto\Schema\FieldPartialUpdateOp\OpType::ARRAY_APPEND),  // Append to array
    (new FieldPartialUpdateOp())
        ->setFieldName('scores')
        ->setOp(\Milvus\Proto\Schema\FieldPartialUpdateOp\OpType::REPLACE),       // Replace (default)
];

$result = $client->upsert(
    'my_collection',
    [['id' => 1, 'tags' => ['new_tag'], 'scores' => [95, 88]]],
    'default',
    '',
    [],
    0,
    true,
    '',
    $fieldOps
);
```

#### Delete

```php
$result = $client->delete('my_collection', 'id in [1, 2, 3]');
$result = $client->delete('my_collection', 'age > 18', 'default', 'my_partition');
```

#### Search

```php
// Simple search (most common use case)
$results = $client->search(
    'my_collection',
    [[0.1, 0.2, 0.3]],      // Query vectors
    'vector',                // Vector field
    10,                      // Top K
    ['nprobe' => 16],        // Search params
    ['text'],                // Output fields
    'text like "doc%"',      // Filter expression
    'default'                // Database name
);

// Results as rows with id, score, and output fields
$rows = $results->toArray();
// [
//     ['id' => 1, 'score' => 0.5, 'text' => 'doc1'],
//     ['id' => 2, 'score' => 0.8, 'text' => 'doc2'],
// ]
```

#### Query

```php
// Basic query
$results = $client->query('my_collection', 'id > 10', ['id', 'text']);

// Query with pagination
$results = $client->query('my_collection', 'age > 18', ['id', 'name'], 'default', 10, 0);
```

### Flush Operations

```php
$result = $client->flush('my_collection');  // Returns FlushResult
$client->flushAll();
$flushed = $client->getFlushState([1, 2, 3]);
```

### Alias Operations

```php
$client->createAlias('my_collection', 'my_alias');
$client->dropAlias('my_alias');
$client->alterAlias('new_collection', 'my_alias');
$info = $client->describeAlias('my_alias');  // Returns AliasDescriptor
$aliases = $client->listAliases('my_collection');
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
$result = $client->import('my_collection', ['data.json']);  // Returns ImportResult
$state = $client->getImportState(12345);  // Returns ImportState
$tasks = $client->listImportTasks('my_collection', 10);  // Returns ImportTasks
```

### Compaction Operations

```php
$result = $client->manualCompaction(12345);  // Returns CompactionResult
$state = $client->getCompactionState(12345);  // Returns CompactionState
```

### Analyzer Operations

```php
// Simple analyzer call
$result = $client->runAnalyzer('Hello world', ['type' => 'chinese'], true);

// Full control with parameters
$result = $client->runAnalyzer(
    'text to analyze',
    ['type' => 'chinese'],
    true,           // withDetail
    'my_collection',
    'text_field',
    'default'
);

$tokens = $result->getResults();
```

### System Operations

```php
$version = $client->getVersion();
$health = $client->checkHealth();  // Returns HealthInfo
$states = $client->getComponentStates();
$metrics = $client->getMetrics('{"metric_key":"*"}');
```

## Response Objects

All response objects provide getter methods and a `toArray()` method where applicable:

| Response Class | Description |
|---------------|-------------|
| `DatabaseDescriptor` | Database information |
| `CollectionInfo` | Collection schema and metadata |
| `CollectionStats` | Collection statistics (row count, data size) |
| `IndexInfo` | Index description |
| `IndexStateInfo` | Index build state |
| `IndexBuildProgress` | Index build progress |
| `MutationResult` | Insert/upsert/delete result |
| `SearchResult` | Search results with IDs and scores |
| `QueryResult` | Query results |
| `FlushResult` | Flush operation result |
| `LoadingProgress` | Collection loading progress |
| `AliasDescriptor` | Alias information |
| `ImportResult` | Import task result |
| `ImportState` | Import task state |
| `ImportTasks` | List of import tasks |
| `CompactionResult` | Compaction result |
| `CompactionState` | Compaction state |
| `PartitionList` | List of partitions |
| `ReplicaInfo` | Replica information |
| `SegmentInfo` | Segment information |
| `HealthInfo` | Health check result |
| `RunAnalyzerResult` | Analyzer tokenization result |

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