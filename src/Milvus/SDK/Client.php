<?php
namespace Milvus\SDK;

use Grpc\BaseStub;
use Grpc\ChannelCredentials;
use Google\Protobuf\Internal\Message;
use Milvus\Proto\Common\Status;
use Milvus\Proto\Common\KeyValuePair;
use Milvus\Proto\Milvus\GetVersionRequest;
use Milvus\Proto\Milvus\GetVersionResponse;
use Milvus\Proto\Milvus\CheckHealthRequest;
use Milvus\Proto\Milvus\CheckHealthResponse;
use Milvus\Proto\Milvus\ConnectRequest;
use Milvus\Proto\Milvus\ConnectResponse;
use Milvus\Proto\Milvus\ListDatabasesRequest;
use Milvus\Proto\Milvus\ListDatabasesResponse;
use Milvus\Proto\Milvus\CreateDatabaseRequest;
use Milvus\Proto\Milvus\DropDatabaseRequest;
use Milvus\Proto\Milvus\DescribeDatabaseRequest;
use Milvus\Proto\Milvus\DescribeDatabaseResponse;
use Milvus\Proto\Milvus\AlterDatabaseRequest;
use Milvus\Proto\Milvus\ShowCollectionsRequest;
use Milvus\Proto\Milvus\ShowCollectionsResponse;
use Milvus\Proto\Milvus\CreateCollectionRequest;
use Milvus\Proto\Milvus\DropCollectionRequest;
use Milvus\Proto\Milvus\HasCollectionRequest;
use Milvus\Proto\Milvus\BoolResponse;
use Milvus\Proto\Milvus\DescribeCollectionRequest;
use Milvus\Proto\Milvus\DescribeCollectionResponse;
use Milvus\Proto\Milvus\LoadCollectionRequest;
use Milvus\Proto\Milvus\ReleaseCollectionRequest;
use Milvus\Proto\Milvus\RenameCollectionRequest;
use Milvus\Proto\Milvus\AlterCollectionRequest;
use Milvus\Proto\Milvus\GetCollectionStatisticsRequest;
use Milvus\Proto\Milvus\GetCollectionStatisticsResponse;
use Milvus\Proto\Milvus\CreatePartitionRequest;
use Milvus\Proto\Milvus\DropPartitionRequest;
use Milvus\Proto\Milvus\HasPartitionRequest;
use Milvus\Proto\Milvus\ShowPartitionsRequest;
use Milvus\Proto\Milvus\ShowPartitionsResponse;
use Milvus\Proto\Milvus\LoadPartitionsRequest;
use Milvus\Proto\Milvus\ReleasePartitionsRequest;
use Milvus\Proto\Milvus\CreateIndexRequest;
use Milvus\Proto\Milvus\DescribeIndexRequest;
use Milvus\Proto\Milvus\DescribeIndexResponse;
use Milvus\Proto\Milvus\DropIndexRequest;
use Milvus\Proto\Milvus\GetIndexStateRequest;
use Milvus\Proto\Milvus\GetIndexStateResponse;
use Milvus\Proto\Milvus\GetIndexBuildProgressRequest;
use Milvus\Proto\Milvus\GetIndexBuildProgressResponse;
use Milvus\Proto\Milvus\GetIndexStatisticsRequest;
use Milvus\Proto\Milvus\GetIndexStatisticsResponse;
use Milvus\Proto\Milvus\InsertRequest;
use Milvus\Proto\Milvus\UpsertRequest;
use Milvus\Proto\Milvus\DeleteRequest;
use Milvus\Proto\Milvus\MutationResult;
use Milvus\Proto\Milvus\SearchRequest;
use Milvus\Proto\Milvus\SearchResults;
use Milvus\Proto\Milvus\HybridSearchRequest;
use Milvus\Proto\Milvus\QueryRequest;
use Milvus\Proto\Milvus\QueryResults;
use Milvus\Proto\Milvus\FlushRequest;
use Milvus\Proto\Milvus\FlushResponse;
use Milvus\Proto\Milvus\GetFlushStateRequest;
use Milvus\Proto\Milvus\GetFlushStateResponse;
use Milvus\Proto\Milvus\GetLoadingProgressRequest;
use Milvus\Proto\Milvus\GetLoadingProgressResponse;
use Milvus\Proto\Milvus\GetLoadStateRequest;
use Milvus\Proto\Milvus\GetLoadStateResponse;
use Milvus\Proto\Milvus\CreateAliasRequest;
use Milvus\Proto\Milvus\DropAliasRequest;
use Milvus\Proto\Milvus\AlterAliasRequest;
use Milvus\Proto\Milvus\DescribeAliasRequest;
use Milvus\Proto\Milvus\DescribeAliasResponse;
use Milvus\Proto\Milvus\ListAliasesRequest;
use Milvus\Proto\Milvus\ListAliasesResponse;
use Milvus\Proto\Milvus\CreateCredentialRequest;
use Milvus\Proto\Milvus\UpdateCredentialRequest;
use Milvus\Proto\Milvus\DeleteCredentialRequest;
use Milvus\Proto\Milvus\ListCredUsersRequest;
use Milvus\Proto\Milvus\ListCredUsersResponse;
use Milvus\Proto\Milvus\CreateRoleRequest;
use Milvus\Proto\Milvus\DropRoleRequest;
use Milvus\Proto\Milvus\RoleEntity;
use Milvus\Proto\Milvus\OperateUserRoleRequest;
use Milvus\Proto\Milvus\SelectRoleRequest;
use Milvus\Proto\Milvus\SelectRoleResponse;
use Milvus\Proto\Milvus\SelectUserRequest;
use Milvus\Proto\Milvus\SelectUserResponse;
use Milvus\Proto\Milvus\GetMetricsRequest;
use Milvus\Proto\Milvus\GetMetricsResponse;
use Milvus\Proto\Milvus\ComponentStates;
use Milvus\Proto\Milvus\GetComponentStatesRequest;
use Milvus\Proto\Milvus\CreateResourceGroupRequest;
use Milvus\Proto\Milvus\DropResourceGroupRequest;
use Milvus\Proto\Milvus\ListResourceGroupsRequest;
use Milvus\Proto\Milvus\ListResourceGroupsResponse;
use Milvus\Proto\Milvus\DescribeResourceGroupRequest;
use Milvus\Proto\Milvus\DescribeResourceGroupResponse;
use Milvus\Proto\Milvus\ImportRequest;
use Milvus\Proto\Milvus\ImportResponse;
use Milvus\Proto\Milvus\GetImportStateRequest;
use Milvus\Proto\Milvus\GetImportStateResponse;
use Milvus\Proto\Milvus\ListImportTasksRequest;
use Milvus\Proto\Milvus\ListImportTasksResponse;
use Milvus\Proto\Milvus\ManualCompactionRequest;
use Milvus\Proto\Milvus\ManualCompactionResponse;
use Milvus\Proto\Milvus\GetCompactionStateRequest;
use Milvus\Proto\Milvus\GetCompactionStateResponse;
use Milvus\Proto\Milvus\TruncateCollectionRequest;
use Milvus\Proto\Milvus\GetReplicasRequest;
use Milvus\Proto\Milvus\GetReplicasResponse;
use Milvus\Proto\Milvus\TransferReplicaRequest;
use Milvus\Proto\Milvus\GetPersistentSegmentInfoRequest;
use Milvus\Proto\Milvus\GetPersistentSegmentInfoResponse;
use Milvus\Proto\Milvus\GetQuerySegmentInfoRequest;
use Milvus\Proto\Milvus\GetQuerySegmentInfoResponse;
use Milvus\Proto\Milvus\FlushAllRequest;
use Milvus\Proto\Milvus\FlushAllResponse;
use Milvus\Proto\Milvus\GetFlushAllStateRequest;
use Milvus\Proto\Milvus\GetFlushAllStateResponse;
use Milvus\SDK\Exceptions\ConnectionException;
use Milvus\SDK\Exceptions\MilvusException;

class Client extends BaseStub
{
    private string $database;
    private string $host;
    private int $port;
    private ?string $token;
    private ?string $username;
    private ?string $password;
    private bool $ssl;
    private int $timeout;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct(array $config = [])
    {
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? 19530;
        $this->database = $config['database'] ?? 'default';
        $this->token = $config['token'] ?? null;
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
        $this->ssl = $config['ssl'] ?? false;
        $this->timeout = $config['timeout'] ?? 30;
        $this->maxRetries = $config['max_retries'] ?? 3;
        $this->retryDelay = $config['retry_delay'] ?? 10;

        $address = "{$this->host}:{$this->port}";

        $opts = [];

        if ($this->ssl) {
            $opts['credentials'] = ChannelCredentials::createSsl();
        } else {
            $opts['credentials'] = ChannelCredentials::createInsecure();
        }

        if ($this->timeout) {
            $opts['timeout'] = $this->timeout * 1000;
        }

        parent::__construct($address, $opts);
    }

    public function call(string $method, Message $request, string $responseClass): mixed
    {
        $metadata = [];

        if ($this->token) {
            $metadata['authorization'] = ['Bearer ' . $this->token];
        } elseif ($this->username && $this->password) {
            $credentials = base64_encode("{$this->username}:{$this->password}");
            $metadata['authorization'] = ['Basic ' . $credentials];
        }

        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                list($response, $status) = $this->_simpleRequest(
                    '/milvus.proto.milvus.MilvusService/' . $method,
                    $request,
                    [$responseClass, 'decode'],
                    $metadata
                )->wait();

                if ($status->code !== 0) {
                    throw new MilvusException("{$method} failed: " . $status->details, $status->code);
                }

                if ($response instanceof Status && $response->getCode() !== 0) {
                    throw new MilvusException("{$method} failed: " . $response->getReason(), $response->getCode());
                }

                if (method_exists($response, 'getStatus') && $response->getStatus() !== null) {
                    $status = $response->getStatus();
                    if ($status instanceof Status && $status->getCode() !== 0) {
                        throw new MilvusException("{$method} failed: " . $status->getReason(), $status->getCode());
                    }
                }

                return $response;
            } catch (MilvusException $e) {
                throw $e;
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000);
                }
            }
        }

        throw new ConnectionException("{$method} failed after {$this->maxRetries} retries: " . $lastException->getMessage(), 0, $lastException);
    }

    public function getDefaultDatabase(): string
    {
        return $this->database;
    }

    public function setDefaultDatabase(string $database): void
    {
        $this->database = $database;
    }

    // ========== System ==========

    public function getVersion(): string
    {
        return $this->call('GetVersion', new GetVersionRequest(), GetVersionResponse::class)->getVersion();
    }

    public function checkHealth(): CheckHealthResponse
    {
        return $this->call('CheckHealth', new CheckHealthRequest(), CheckHealthResponse::class);
    }

    public function connect(): ConnectResponse
    {
        return $this->call('Connect', new ConnectRequest(), ConnectResponse::class);
    }

    public function getComponentStates(): ComponentStates
    {
        return $this->call('GetComponentStates', new GetComponentStatesRequest(), ComponentStates::class);
    }

    public function getMetrics(string $requestStr): string
    {
        $resp = $this->call('GetMetrics', (new GetMetricsRequest())->setRequest($requestStr), GetMetricsResponse::class);
        return $resp->getResponse();
    }

    // ========== Database ==========

    public function listDatabases(): array
    {
        $resp = $this->call('ListDatabases', new ListDatabasesRequest(), ListDatabasesResponse::class);
        $names = $resp->getDbNames();
        return $names instanceof \Google\Protobuf\RepeatedField ? iterator_to_array($names) : (array)$names;
    }

    public function createDatabase(string $name): void
    {
        $this->call('CreateDatabase', (new CreateDatabaseRequest())->setDbName($name), Status::class);
    }

    public function dropDatabase(string $name): void
    {
        $this->call('DropDatabase', (new DropDatabaseRequest())->setDbName($name), Status::class);
    }

    public function describeDatabase(string $name): DescribeDatabaseResponse
    {
        return $this->call('DescribeDatabase', (new DescribeDatabaseRequest())->setDbName($name), DescribeDatabaseResponse::class);
    }

    public function alterDatabase(string $name): void
    {
        $this->call('AlterDatabase', (new AlterDatabaseRequest())->setDbName($name), Status::class);
    }

    // ========== Collection ==========

    public function showCollections(?string $dbName = null): array
    {
        $resp = $this->call('ShowCollections', (new ShowCollectionsRequest())->setDbName($dbName ?? $this->database), ShowCollectionsResponse::class);
        $names = $resp->getCollectionNames();
        return $names instanceof \Google\Protobuf\RepeatedField ? iterator_to_array($names) : (array)$names;
    }

    public function createCollection(string $name, string $schema, ?string $dbName = null, int $shardsNum = 1): void
    {
        $req = (new CreateCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name)
            ->setSchema($schema)
            ->setShardsNum($shardsNum);
        $this->call('CreateCollection', $req, Status::class);
    }

    public function dropCollection(string $name, ?string $dbName = null): void
    {
        $req = (new DropCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name);
        $this->call('DropCollection', $req, Status::class);
    }

    public function hasCollection(string $name, ?string $dbName = null): bool
    {
        $resp = $this->call('HasCollection', (new HasCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name), BoolResponse::class);
        return $resp->getValue();
    }

    public function describeCollection(string $name, ?string $dbName = null): DescribeCollectionResponse
    {
        return $this->call('DescribeCollection', (new DescribeCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name), DescribeCollectionResponse::class);
    }

    public function loadCollection(string $name, ?string $dbName = null): void
    {
        $this->call('LoadCollection', (new LoadCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name), Status::class);
    }

    public function releaseCollection(string $name, ?string $dbName = null): void
    {
        $this->call('ReleaseCollection', (new ReleaseCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name), Status::class);
    }

    public function renameCollection(string $name, string $newName, ?string $dbName = null): void
    {
        $this->call('RenameCollection', (new RenameCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setOldName($name)
            ->setNewName($newName), Status::class);
    }

    public function truncateCollection(string $name, ?string $dbName = null): void
    {
        $this->call('TruncateCollection', (new TruncateCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name), Status::class);
    }

    public function alterCollection(string $name, KeyValuePair $properties, ?string $dbName = null): void
    {
        $req = (new AlterCollectionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name)
            ->setProperties([$properties]);
        $this->call('AlterCollection', $req, Status::class);
    }

    public function getCollectionStatistics(string $name, ?string $dbName = null): GetCollectionStatisticsResponse
    {
        return $this->call('GetCollectionStatistics', (new GetCollectionStatisticsRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($name), GetCollectionStatisticsResponse::class);
    }

    // ========== Partition ==========

    public function createPartition(string $collectionName, string $partitionName, ?string $dbName = null): void
    {
        $this->call('CreatePartition', (new CreatePartitionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setPartitionName($partitionName), Status::class);
    }

    public function dropPartition(string $collectionName, string $partitionName, ?string $dbName = null): void
    {
        $this->call('DropPartition', (new DropPartitionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setPartitionName($partitionName), Status::class);
    }

    public function hasPartition(string $collectionName, string $partitionName, ?string $dbName = null): bool
    {
        $resp = $this->call('HasPartition', (new HasPartitionRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setPartitionName($partitionName), BoolResponse::class);
        return $resp->getValue();
    }

    public function showPartitions(string $collectionName, ?string $dbName = null): ShowPartitionsResponse
    {
        return $this->call('ShowPartitions', (new ShowPartitionsRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName), ShowPartitionsResponse::class);
    }

    public function loadPartitions(string $collectionName, array $partitionNames, ?string $dbName = null): void
    {
        $req = (new LoadPartitionsRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setPartitionNames($partitionNames);
        $this->call('LoadPartitions', $req, Status::class);
    }

    public function releasePartitions(string $collectionName, array $partitionNames, ?string $dbName = null): void
    {
        $req = (new ReleasePartitionsRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setPartitionNames($partitionNames);
        $this->call('ReleasePartitions', $req, Status::class);
    }

    // ========== Index ==========

    public function createIndex(string $collectionName, string $fieldName, $indexType = 'FLAT', ?string $dbName = null, array $extraParams = []): void
    {
        $req = (new CreateIndexRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setFieldName($fieldName);
        
        if (is_int($indexType)) {
            $indexType = $this->indexTypeIntToString($indexType);
        }
        
        $defaultParams = [
            'index_type' => $indexType,
            'metric_type' => 'L2',
        ];
        
        $params = array_merge($defaultParams, $extraParams);
        $kvPairs = [];
        foreach ($params as $k => $v) {
            $kvPairs[] = (new KeyValuePair())->setKey($k)->setValue((string)$v);
        }
        $req->setExtraParams($kvPairs);
        
        $this->call('CreateIndex', $req, Status::class);
    }

    private function indexTypeIntToString(int $indexType): string
    {
        $map = [
            0 => 'INVALID',
            1 => 'FLAT',
            2 => 'IVFFLAT',
            3 => 'IVFSQ8',
            4 => 'IVFPQ',
            5 => 'HNSW',
            10 => 'DISKANN',
            50 => 'AUTOINDEX',
            55 => 'GPUIVFFLAT',
            56 => 'GPUIVFSQ8',
            80 => 'BINFLAT',
            81 => 'BINIVFFLAT',
            90 => 'TANTAMI',
            100 => 'SPARSEINVERTEDINDEX',
            101 => 'SPARSEWAND',
        ];
        return $map[$indexType] ?? 'FLAT';
    }

    public function describeIndex(string $collectionName, string $fieldName = '', string $indexName = '', ?string $dbName = null): DescribeIndexResponse
    {
        $req = (new DescribeIndexRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName);
        if ($fieldName) $req->setFieldName($fieldName);
        if ($indexName) $req->setIndexName($indexName);
        return $this->call('DescribeIndex', $req, DescribeIndexResponse::class);
    }

    public function dropIndex(string $collectionName, string $fieldName = '', string $indexName = '', ?string $dbName = null): void
    {
        $req = (new DropIndexRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName);
        if ($fieldName) $req->setFieldName($fieldName);
        if ($indexName) $req->setIndexName($indexName);
        $this->call('DropIndex', $req, Status::class);
    }

    public function getIndexState(string $collectionName, string $fieldName = '', ?string $dbName = null): GetIndexStateResponse
    {
        $req = (new GetIndexStateRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName);
        if ($fieldName) $req->setFieldName($fieldName);
        return $this->call('GetIndexState', $req, GetIndexStateResponse::class);
    }

    public function getIndexBuildProgress(string $collectionName, string $fieldName = '', ?string $dbName = null): GetIndexBuildProgressResponse
    {
        $req = (new GetIndexBuildProgressRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName);
        if ($fieldName) $req->setFieldName($fieldName);
        return $this->call('GetIndexBuildProgress', $req, GetIndexBuildProgressResponse::class);
    }

    public function getIndexStatistics(string $collectionName, string $indexName = '', ?string $dbName = null): GetIndexStatisticsResponse
    {
        $req = (new GetIndexStatisticsRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName);
        if ($indexName) $req->setIndexName($indexName);
        return $this->call('GetIndexStatistics', $req, GetIndexStatisticsResponse::class);
    }

    // ========== Data ==========

    public function insert(string $collectionName, array $fieldsData, ?string $dbName = null): MutationResult
    {
        $numRows = 0;
        if (!empty($fieldsData)) {
            $firstField = $fieldsData[0];
            if ($firstField->getVectors()) {
                $vectors = $firstField->getVectors();
                $numRows = count($vectors->getFloatVector()->getData()) / $vectors->getDim();
            } elseif ($firstField->getScalars()) {
                $scalars = $firstField->getScalars();
                if ($scalars->getLongData()) {
                    $numRows = count($scalars->getLongData()->getData());
                } elseif ($scalars->getIntData()) {
                    $numRows = count($scalars->getIntData()->getData());
                } elseif ($scalars->getFloatData()) {
                    $numRows = count($scalars->getFloatData()->getData());
                } elseif ($scalars->getStringData()) {
                    $numRows = count($scalars->getStringData()->getData());
                }
            }
        }
        
        $req = (new InsertRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setFieldsData($fieldsData)
            ->setNumRows((int)$numRows);
        return $this->call('Insert', $req, MutationResult::class);
    }

    public function upsert(string $collectionName, array $fieldsData, ?string $dbName = null): MutationResult
    {
        $numRows = 0;
        if (!empty($fieldsData)) {
            $firstField = $fieldsData[0];
            if ($firstField->getVectors()) {
                $vectors = $firstField->getVectors();
                $numRows = count($vectors->getFloatVector()->getData()) / $vectors->getDim();
            } elseif ($firstField->getScalars()) {
                $scalars = $firstField->getScalars();
                if ($scalars->getLongData()) {
                    $numRows = count($scalars->getLongData()->getData());
                } elseif ($scalars->getIntData()) {
                    $numRows = count($scalars->getIntData()->getData());
                } elseif ($scalars->getFloatData()) {
                    $numRows = count($scalars->getFloatData()->getData());
                } elseif ($scalars->getStringData()) {
                    $numRows = count($scalars->getStringData()->getData());
                }
            }
        }
        
        $req = (new UpsertRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setFieldsData($fieldsData)
            ->setNumRows((int)$numRows);
        return $this->call('Upsert', $req, MutationResult::class);
    }

    public function delete(string $collectionName, string $expr, ?string $dbName = null, string $partitionName = ''): MutationResult
    {
        $req = (new DeleteRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setExpr($expr);
        if ($partitionName) $req->setPartitionName($partitionName);
        return $this->call('Delete', $req, MutationResult::class);
    }

    public function search(SearchRequest $request): SearchResults
    {
        return $this->call('Search', $request, SearchResults::class);
    }

    public function hybridSearch(HybridSearchRequest $request): SearchResults
    {
        return $this->call('HybridSearch', $request, SearchResults::class);
    }

    public function query(string $collectionName, string $expr, array $outputFields = [], ?string $dbName = null): QueryResults
    {
        $req = (new QueryRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setExpr($expr);
        if ($outputFields) $req->setOutputFields($outputFields);
        return $this->call('Query', $req, QueryResults::class);
    }

    // ========== Flush ==========

    public function flush(string $collectionName, ?string $dbName = null): FlushResponse
    {
        $req = (new FlushRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionNames([$collectionName]);
        return $this->call('Flush', $req, FlushResponse::class);
    }

    public function flushAll(): FlushAllResponse
    {
        return $this->call('FlushAll', new FlushAllRequest(), FlushAllResponse::class);
    }

    public function getFlushState(array $segmentIDs): bool
    {
        $req = (new GetFlushStateRequest())->setSegmentIDs($segmentIDs);
        $resp = $this->call('GetFlushState', $req, GetFlushStateResponse::class);
        return $resp->getFlushed();
    }

    public function getFlushAllState(int $flushAllTs): bool
    {
        $req = (new GetFlushAllStateRequest())->setFlushAllTs($flushAllTs);
        $resp = $this->call('GetFlushAllState', $req, GetFlushAllStateResponse::class);
        return $resp->getFlushed();
    }

    // ========== Loading ==========

    public function getLoadingProgress(string $collectionName, ?string $dbName = null): GetLoadingProgressResponse
    {
        return $this->call('GetLoadingProgress', (new GetLoadingProgressRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName), GetLoadingProgressResponse::class);
    }

    public function getLoadState(string $collectionName, ?string $dbName = null): int
    {
        $resp = $this->call('GetLoadState', (new GetLoadStateRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName), GetLoadStateResponse::class);
        return $resp->getState();
    }

    // ========== Alias ==========

    public function createAlias(string $collectionName, string $alias, ?string $dbName = null): void
    {
        $this->call('CreateAlias', (new CreateAliasRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setAlias($alias), Status::class);
    }

    public function dropAlias(string $alias, ?string $dbName = null): void
    {
        $this->call('DropAlias', (new DropAliasRequest())
            ->setDbName($dbName ?? $this->database)
            ->setAlias($alias), Status::class);
    }

    public function alterAlias(string $collectionName, string $alias, ?string $dbName = null): void
    {
        $this->call('AlterAlias', (new AlterAliasRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setAlias($alias), Status::class);
    }

    public function describeAlias(string $alias, ?string $dbName = null): DescribeAliasResponse
    {
        return $this->call('DescribeAlias', (new DescribeAliasRequest())
            ->setDbName($dbName ?? $this->database)
            ->setAlias($alias), DescribeAliasResponse::class);
    }

    public function listAliases(string $collectionName = '', ?string $dbName = null): array
    {
        $req = (new ListAliasesRequest())->setDbName($dbName ?? $this->database);
        if ($collectionName) $req->setCollectionName($collectionName);
        $resp = $this->call('ListAliases', $req, ListAliasesResponse::class);
        $aliases = $resp->getAliases();
        return $aliases instanceof \Google\Protobuf\RepeatedField ? iterator_to_array($aliases) : (array)$aliases;
    }

    // ========== Auth ==========

    public function createCredential(string $username, string $password): void
    {
        $this->call('CreateCredential', (new CreateCredentialRequest())
            ->setUsername($username)
            ->setPassword($password), Status::class);
    }

    public function updateCredential(string $username, string $oldPassword, string $newPassword): void
    {
        $this->call('UpdateCredential', (new UpdateCredentialRequest())
            ->setUsername($username)
            ->setOldPassword($oldPassword)
            ->setNewPassword($newPassword), Status::class);
    }

    public function deleteCredential(string $username): void
    {
        $this->call('DeleteCredential', (new DeleteCredentialRequest())
            ->setUsername($username), Status::class);
    }

    public function listCredUsers(): array
    {
        $resp = $this->call('ListCredUsers', new ListCredUsersRequest(), ListCredUsersResponse::class);
        $usernames = $resp->getUsernames();
        return $usernames instanceof \Google\Protobuf\RepeatedField ? iterator_to_array($usernames) : (array)$usernames;
    }

    // ========== RBAC ==========

    public function createRole(string $roleName): void
    {
        $this->call('CreateRole', (new CreateRoleRequest())->setEntity(
            (new RoleEntity())->setName($roleName)
        ), Status::class);
    }

    public function dropRole(string $roleName): void
    {
        $this->call('DropRole', (new DropRoleRequest())->setRoleName($roleName), Status::class);
    }

    public function operateUserRole(string $username, string $roleName, int $type): void
    {
        $req = (new OperateUserRoleRequest())
            ->setUsername($username)
            ->setRoleName($roleName)
            ->setType($type);
        $this->call('OperateUserRole', $req, Status::class);
    }

    public function selectRole(string $roleName = ''): SelectRoleResponse
    {
        $req = new SelectRoleRequest();
        if ($roleName) $req->setRole((new RoleEntity())->setName($roleName));
        return $this->call('SelectRole', $req, SelectRoleResponse::class);
    }

    public function selectUser(string $username = ''): SelectUserResponse
    {
        $req = new SelectUserRequest();
        if ($username) $req->setUser((new \Milvus\Proto\Milvus\UserEntity())->setName($username));
        return $this->call('SelectUser', $req, SelectUserResponse::class);
    }

    // ========== Resource Group ==========

    public function createResourceGroup(string $name): void
    {
        $this->call('CreateResourceGroup', (new CreateResourceGroupRequest())->setResourceGroup($name), Status::class);
    }

    public function dropResourceGroup(string $name): void
    {
        $this->call('DropResourceGroup', (new DropResourceGroupRequest())->setResourceGroup($name), Status::class);
    }

    public function listResourceGroups(): array
    {
        $resp = $this->call('ListResourceGroups', new ListResourceGroupsRequest(), ListResourceGroupsResponse::class);
        $groups = $resp->getResourceGroups();
        return $groups instanceof \Google\Protobuf\RepeatedField ? iterator_to_array($groups) : (array)$groups;
    }

    public function describeResourceGroup(string $name): DescribeResourceGroupResponse
    {
        return $this->call('DescribeResourceGroup', (new DescribeResourceGroupRequest())->setResourceGroup($name), DescribeResourceGroupResponse::class);
    }

    public function transferReplica(string $sourceGroup, string $targetGroup, string $collectionName, int $numReplica): void
    {
        $this->call('TransferReplica', (new TransferReplicaRequest())
            ->setSourceResourceGroup($sourceGroup)
            ->setTargetResourceGroup($targetGroup)
            ->setCollectionName($collectionName)
            ->setNumReplica($numReplica), Status::class);
    }

    // ========== Import ==========

    public function import(string $collectionName, array $files, ?string $dbName = null): ImportResponse
    {
        $req = (new ImportRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName)
            ->setFiles($files);
        return $this->call('Import', $req, ImportResponse::class);
    }

    public function getImportState(int $taskId): GetImportStateResponse
    {
        return $this->call('GetImportState', (new GetImportStateRequest())->setTask($taskId), GetImportStateResponse::class);
    }

    public function listImportTasks(string $collectionName = '', int $limit = 0, ?string $dbName = null): ListImportTasksResponse
    {
        $req = (new ListImportTasksRequest())->setDbName($dbName ?? $this->database);
        if ($collectionName) $req->setCollectionName($collectionName);
        if ($limit > 0) $req->setLimit($limit);
        return $this->call('ListImportTasks', $req, ListImportTasksResponse::class);
    }

    // ========== Compaction ==========

    public function manualCompaction(int $collectionID): ManualCompactionResponse
    {
        return $this->call('ManualCompaction', (new ManualCompactionRequest())->setCollectionID($collectionID), ManualCompactionResponse::class);
    }

    public function getCompactionState(int $compactionID): GetCompactionStateResponse
    {
        return $this->call('GetCompactionState', (new GetCompactionStateRequest())->setCompactionID($compactionID), GetCompactionStateResponse::class);
    }

    // ========== Segment ==========

    public function getPersistentSegmentInfo(string $collectionName, ?string $dbName = null): GetPersistentSegmentInfoResponse
    {
        return $this->call('GetPersistentSegmentInfo', (new GetPersistentSegmentInfoRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName), GetPersistentSegmentInfoResponse::class);
    }

    public function getQuerySegmentInfo(string $collectionName, ?string $dbName = null): GetQuerySegmentInfoResponse
    {
        return $this->call('GetQuerySegmentInfo', (new GetQuerySegmentInfoRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName), GetQuerySegmentInfoResponse::class);
    }

    // ========== Replica ==========

    public function getReplicas(string $collectionName, ?string $dbName = null): GetReplicasResponse
    {
        return $this->call('GetReplicas', (new GetReplicasRequest())
            ->setDbName($dbName ?? $this->database)
            ->setCollectionName($collectionName), GetReplicasResponse::class);
    }
}