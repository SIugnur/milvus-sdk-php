<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\ListImportTasksResponse;

class ImportTasks
{
    private ListImportTasksResponse $raw;

    public function __construct(ListImportTasksResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getTasks(): array
    {
        $tasks = $this->raw->getTasks();
        if ($tasks === null) return [];
        $result = [];
        foreach ($tasks as $task) {
            $result[] = [
                'id' => $task->getId(),
                'state' => $task->getState(),
                'collection_id' => $task->getCollectionId(),
                'row_count' => $task->getRowCount(),
                'create_ts' => $task->getCreateTs(),
            ];
        }
        return $result;
    }

    public function getRaw(): ListImportTasksResponse
    {
        return $this->raw;
    }
}
