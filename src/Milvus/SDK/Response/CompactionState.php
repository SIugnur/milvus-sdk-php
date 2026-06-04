<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetCompactionStateResponse;

class CompactionState
{
    private GetCompactionStateResponse $raw;

    public function __construct(GetCompactionStateResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getState(): int
    {
        return $this->raw->getState();
    }

    public function getStateName(): string
    {
        $map = [
            0 => 'Undefined',
            1 => 'Executing',
            2 => 'Completed',
            3 => 'Failed',
        ];
        return $map[$this->getState()] ?? 'Unknown';
    }

    public function isCompleted(): bool
    {
        return $this->getState() === 2;
    }

    public function getRaw(): GetCompactionStateResponse
    {
        return $this->raw;
    }
}
