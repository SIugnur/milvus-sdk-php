# Milvus PHP SDK

[Milvus](https://github.com/milvus-io/milvus) 向量数据库的 PHP SDK。提供 gRPC 客户端，支持向量相似度搜索、元数据过滤以及完整的集合/索引/用户管理功能。

**包名**: `siugnur/milvus-sdk-php` | **PHP 版本**: ^8.0 | **许可证**: MIT

## 安装

```bash
composer require siugnur/milvus-sdk-php
```

### 依赖要求

- PHP 8.0 或更高版本
- [gRPC PHP 扩展](https://github.com/grpc/grpc/tree/master/src/php) (`ext-grpc`) — `grpc/grpc` 库必需
- `ext-protobuf`（可选）— 用于更快的 protobuf 序列化

安装所需扩展：

```bash
pecl install grpc
pecl install protobuf   # 可选，但推荐用于性能优化
```

> **注意**: `ext-grpc` 是 `grpc/grpc` Composer 库必需的。运行 `composer require siugnur/milvus-sdk-php` 时，Composer 会自动检查。

## 快速开始

### 连接到 Milvus

```php
use Milvus\SDK\Client;

$client = new Client([
    'host'        => 'localhost',   // Milvus 服务器地址
    'port'        => 19530,         // Milvus 服务器端口
    'token'       => null,          // 可选：认证 token（例如，'root:Milvus'）
    'ssl'         => false,         // 启用 SSL/TLS
    'database'    => 'default',     // 默认数据库名称
    'timeout'     => 30,            // 请求超时时间（秒）
    'max_retries' => 3,             // 最大重试次数
    'retry_delay' => 10,            // 重试延迟（毫秒）
]);
```

### 创建集合、插入数据和搜索

```php
use Milvus\SDK\Client;
use Milvus\SDK\Constants\DataType;

$client = new Client(['host' => 'localhost', 'port' => 19530]);

// 1. 创建集合（使用数组形式的 schema，无需 protobuf！）
$client->createCollection(
    'my_collection',
    [
        ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
        ['name' => 'vector', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
        ['name' => 'text', 'data_type' => DataType::VarChar, 'type_params' => ['max_length' => 512]],
    ],
    '我的测试集合',
    true
);

// 2. 创建索引
$client->createIndex('my_collection', 'vector', null, [
    'index_type' => 'IVFFLAT',
    'metric_type' => 'L2',
    'params' => '{"nlist": 128}',
]);

// 3. 加载集合到内存
$client->loadCollection('my_collection');

// 4. 插入数据（直接传入关联数组！）
$result = $client->insert('my_collection', [
    ['vector' => array_fill(0, 128, 0.1), 'text' => '文档 1'],
    ['vector' => array_fill(0, 128, 0.2), 'text' => '文档 2'],
]);
echo "插入的 ID: " . json_encode($result->getInsertIds()) . "\n";

// 5. 搜索（使用简单参数）
$results = $client->search(
    'my_collection',
    [array_fill(0, 128, 0.15)],  // 查询向量
    'vector',                      // 向量字段名
    10,                           // Top K
    ['nprobe' => 16],              // 搜索参数
    ['text'],                      // 输出字段
    '',                            // 过滤表达式
    null                           // 数据库名称
);

print_r($results->toArray());
```

## API 参考

### 数据库操作

```php
$client->createDatabase('my_db');
$client->dropDatabase('my_db');
$databases = $client->listDatabases();
$info = $client->describeDatabase('my_db');  // 返回 DatabaseDescriptor
$client->alterDatabase('my_db');
```

### 集合操作

```php
// 创建集合（数组形式的 schema）
$client->createCollection(
    'my_collection',
    [
        ['name' => 'id', 'data_type' => DataType::Int64, 'is_primary_key' => true, 'autoID' => true],
        ['name' => 'vec', 'data_type' => DataType::FloatVector, 'type_params' => ['dim' => 128]],
    ],
    '描述信息',
    true,   // enableDynamicField
    'default',  // dbName（可选）
    1       // shardsNum（可选）
);

$client->dropCollection('my_collection');
$exists = $client->hasCollection('my_collection');
$info = $client->describeCollection('my_collection');  // 返回 CollectionInfo
$client->loadCollection('my_collection');
$client->releaseCollection('my_collection');
$collections = $client->showCollections();
$client->renameCollection('old_name', 'new_name');
$client->truncateCollection('my_collection');
$client->alterCollection('my_collection', ['collection.ttl.seconds' => '3600']);
$stats = $client->getCollectionStatistics('my_collection');  // 返回 CollectionStats
```

### 索引操作

```php
$client->createIndex('my_collection', 'vector', null, [
    'index_type' => 'IVFFLAT',
    'metric_type' => 'L2',
    'params' => '{"nlist": 128}',
]);

$info = $client->describeIndex('my_collection', 'vector');  // 返回 IndexInfo
$state = $client->getIndexState('my_collection', 'vector');  // 返回 IndexStateInfo
$progress = $client->getIndexBuildProgress('my_collection', 'vector');  // 返回 IndexBuildProgress
$client->dropIndex('my_collection', 'vector');
```

### 数据操作

#### 插入数据

```php
// 直接传入关联数组（数据类型自动推断）
$result = $client->insert('my_collection', [
    ['vector' => [0.1, 0.2, 0.3], 'text' => '文档1', 'value' => 100],
    ['vector' => [0.4, 0.5, 0.6], 'text' => '文档2', 'value' => 200],
]);
$ids = $result->getInsertIds();

// 带可选参数
$result = $client->insert(
    'my_collection',
    [['vector' => [0.1, 0.2, 0.3], 'text' => '文档1']],
    'default',       // dbName
    'my_partition',  // partitionName
    [1, 2, 3],      // hashKeys
    0,              // schemaTimestamp
    'my_namespace'  // namespace
);
```

#### Upsert（插入或更新）

```php
// 基本 upsert（自动推断类型）
$result = $client->upsert('my_collection', [
    ['id' => 1, 'vector' => [0.1, 0.2, 0.3], 'text' => '已更新'],
    ['id' => 2, 'vector' => [0.4, 0.5, 0.6], 'text' => '新增'],
]);

// 增量更新（只更新指定字段）
$result = $client->upsert(
    'my_collection',
    [['id' => 1, 'value' => 999]],  // 只更新 'value' 字段
    'default',       // dbName
    '',              // partitionName
    [],              // hashKeys
    0,               // schemaTimestamp
    true,            // partialUpdate（增量更新）
    '',              // namespace
    []               // fieldOps
);

// 使用 fieldOps 进行数组字段操作
use Milvus\Proto\Schema\FieldPartialUpdateOp;

$fieldOps = [
    (new FieldPartialUpdateOp())
        ->setFieldName('tags')
        ->setOp(\Milvus\Proto\Schema\FieldPartialUpdateOp\OpType::ARRAY_APPEND),  // 追加到数组
    (new FieldPartialUpdateOp())
        ->setFieldName('scores')
        ->setOp(\Milvus\Proto\Schema\FieldPartialUpdateOp\OpType::REPLACE),       // 替换（默认）
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

#### 删除数据

```php
$result = $client->delete('my_collection', 'id in [1, 2, 3]');
$result = $client->delete('my_collection', 'age > 18', 'default', 'my_partition');
```

#### 搜索

```php
// 简单搜索（最常用）
$results = $client->search(
    'my_collection',
    [[0.1, 0.2, 0.3]],      // 查询向量
    'vector',                // 向量字段
    10,                      // Top K
    ['nprobe' => 16],        // 搜索参数
    ['text'],                // 输出字段
    'text like "doc%"',      // 过滤表达式
    'default'                // 数据库名称
);

// 结果转换为数组（包含 id、score 和输出字段）
$rows = $results->toArray();
// [
//     ['id' => 1, 'score' => 0.5, 'text' => '文档1'],
//     ['id' => 2, 'score' => 0.8, 'text' => '文档2'],
// ]
```

#### 查询

```php
// 基本查询
$results = $client->query('my_collection', 'id > 10', ['id', 'text']);

// 带分页的查询
$results = $client->query('my_collection', 'age > 18', ['id', 'name'], 'default', 10, 0);
```

### Flush 操作

```php
$result = $client->flush('my_collection');  // 返回 FlushResult
$client->flushAll();
$flushed = $client->getFlushState([1, 2, 3]);
```

### 别名操作

```php
$client->createAlias('my_collection', 'my_alias');
$client->dropAlias('my_alias');
$client->alterAlias('new_collection', 'my_alias');
$info = $client->describeAlias('my_alias');  // 返回 AliasDescriptor
$aliases = $client->listAliases('my_collection');
```

### 用户与角色管理（RBAC）

```php
// 用户
$client->createCredential('username', 'password');
$client->updateCredential('username', 'old_pw', 'new_pw');
$client->deleteCredential('username');
$users = $client->listCredUsers();

// 角色
$client->createRole('admin');
$client->dropRole('admin');
$client->operateUserRole('username', 'admin', \Milvus\SDK\Constants\OperateUserRoleType::AddUserToRole);
```

### 资源组操作

```php
$client->createResourceGroup('group1');
$client->dropResourceGroup('group1');
$groups = $client->listResourceGroups();
$info = $client->describeResourceGroup('group1');
$client->transferReplica('source', 'target', 'collection', 2);
```

### 导入操作

```php
$result = $client->import('my_collection', ['data.json']);  // 返回 ImportResult
$state = $client->getImportState(12345);  // 返回 ImportState
$tasks = $client->listImportTasks('my_collection', 10);  // 返回 ImportTasks
```

### 压缩操作

```php
$result = $client->manualCompaction(12345);  // 返回 CompactionResult
$state = $client->getCompactionState(12345);  // 返回 CompactionState
```

### 分词器操作

```php
// 简单调用
$result = $client->runAnalyzer('Hello world', ['type' => 'chinese'], true);

// 完整参数
$result = $client->runAnalyzer(
    '要分析的文本',
    ['type' => 'chinese'],
    true,           // withDetail
    'my_collection',
    'text_field',
    'default'
);

$tokens = $result->getResults();
```

### 系统操作

```php
$version = $client->getVersion();
$health = $client->checkHealth();  // 返回 HealthInfo
$states = $client->getComponentStates();
$metrics = $client->getMetrics('{"metric_key":"*"}');
```

## 响应对象

所有响应对象都提供 getter 方法和 `toArray()` 方法：

| 响应类 | 描述 |
|--------|------|
| `DatabaseDescriptor` | 数据库信息 |
| `CollectionInfo` | 集合 schema 和元数据 |
| `CollectionStats` | 集合统计信息（行数、数据大小） |
| `IndexInfo` | 索引描述 |
| `IndexStateInfo` | 索引构建状态 |
| `IndexBuildProgress` | 索引构建进度 |
| `MutationResult` | 插入/更新/删除结果 |
| `SearchResult` | 搜索结果（包含 ID 和分数） |
| `QueryResult` | 查询结果 |
| `FlushResult` | Flush 操作结果 |
| `LoadingProgress` | 集合加载进度 |
| `AliasDescriptor` | 别名信息 |
| `ImportResult` | 导入任务结果 |
| `ImportState` | 导入任务状态 |
| `ImportTasks` | 导入任务列表 |
| `CompactionResult` | 压缩结果 |
| `CompactionState` | 压缩状态 |
| `PartitionList` | 分区列表 |
| `ReplicaInfo` | 副本信息 |
| `SegmentInfo` | 段信息 |
| `HealthInfo` | 健康检查结果 |
| `RunAnalyzerResult` | 分词结果 |

## 错误处理

```php
use Milvus\SDK\Exceptions\MilvusException;
use Milvus\SDK\Exceptions\ConnectionException;

try {
    $client->loadCollection('non_existent');
} catch (ConnectionException $e) {
    echo "连接错误: " . $e->getMessage();
} catch (MilvusException $e) {
    echo "Milvus 错误 (代码 {$e->getStatusCode()}): " . $e->getMessage();
}
```

## 运行测试

```bash
# 运行所有测试（需要运行中的 Milvus 实例）
./vendor/bin/phpunit

# 带覆盖率的测试
./vendor/bin/phpunit --coverage-html ./coverage

# 指定自定义 Milvus 地址/端口
MILVUS_HOST=192.168.1.100 MILVUS_PORT=19530 ./vendor/bin/phpunit
```

## 许可证

MIT
