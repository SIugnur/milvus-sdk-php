<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetImportStateResponse;

class ImportState
{
    private GetImportStateResponse $raw;

    public function __construct(GetImportStateResponse $raw)
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
            0 => 'Pending',
            1 => 'Started',
            2 => 'Completed',
            3 => 'Failed',
        ];
        return $map[$this->getState()] ?? 'Unknown';
    }

    public function isCompleted(): bool
    {
        return $this->getState() === 2;
    }

    public function isFailed(): bool
    {
        return $this->getState() === 3;
    }

    public function getRowCount(): int
    {
        return $this->raw->getRowCount();
    }

    public function getFileSize(): int
    {
        return $this->raw->getFileSize();
    }

    public function getRaw(): GetImportStateResponse
    {
        return $this->raw;
    }
}
