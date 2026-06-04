<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\ImportResponse;

class ImportResult
{
    private ImportResponse $raw;

    public function __construct(ImportResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getTaskIDs(): array
    {
        $ids = $this->raw->getTasks();
        return $ids ? iterator_to_array($ids) : [];
    }

    public function getRaw(): ImportResponse
    {
        return $this->raw;
    }
}
