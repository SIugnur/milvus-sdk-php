<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\CheckHealthResponse;

class HealthInfo
{
    private CheckHealthResponse $raw;

    public function __construct(CheckHealthResponse $raw)
    {
        $this->raw = $raw;
    }

    public function isHealthy(): bool
    {
        return $this->raw->getIsHealthy();
    }

    public function getReasons(): array
    {
        $reasons = $this->raw->getReasons();
        return $reasons ? iterator_to_array($reasons) : [];
    }

    public function getRaw(): CheckHealthResponse
    {
        return $this->raw;
    }
}