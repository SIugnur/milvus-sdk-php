<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\ManualCompactionResponse;

class CompactionResult
{
    private ManualCompactionResponse $raw;

    public function __construct(ManualCompactionResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getCompactionID(): int
    {
        return $this->raw->getCompactionID();
    }

    public function getRaw(): ManualCompactionResponse
    {
        return $this->raw;
    }
}
