<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\GetLoadingProgressResponse;

class LoadingProgress
{
    private GetLoadingProgressResponse $raw;

    public function __construct(GetLoadingProgressResponse $raw)
    {
        $this->raw = $raw;
    }

    public function getProgress(): int
    {
        return $this->raw->getProgress();
    }

    public function isComplete(): bool
    {
        return $this->getProgress() >= 100;
    }

    public function getRaw(): GetLoadingProgressResponse
    {
        return $this->raw;
    }
}
