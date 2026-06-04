<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetIndexStateResponse;

class IndexStateInfo
{
    private GetIndexStateResponse $raw;

    public function __construct(GetIndexStateResponse $raw)
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
            0 => 'None',
            1 => 'Unissued',
            2 => 'InProgress',
            3 => 'Finished',
            4 => 'Failed',
            5 => 'Retry',
        ];
        return $map[$this->getState()] ?? 'Unknown';
    }

    public function isFinished(): bool
    {
        return $this->getState() === 3;
    }

    public function isFailed(): bool
    {
        return $this->getState() === 4;
    }

    public function getRaw(): GetIndexStateResponse
    {
        return $this->raw;
    }
}
