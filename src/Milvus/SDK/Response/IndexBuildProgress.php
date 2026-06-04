<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetIndexBuildProgressResponse;

class IndexBuildProgress
{
    private GetIndexBuildProgressResponse $raw;

    public function __construct(GetIndexBuildProgressResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getTotalRows(): int
    {
        return $this->raw->getTotalRows();
    }

    public function getIndexedRows(): int
    {
        return $this->raw->getIndexedRows();
    }

    public function getProgress(): float
    {
        $total = $this->getTotalRows();
        if ($total === 0) return 1.0;
        return $this->getIndexedRows() / $total;
    }

    public function getRaw(): GetIndexBuildProgressResponse
    {
        return $this->raw;
    }
}
