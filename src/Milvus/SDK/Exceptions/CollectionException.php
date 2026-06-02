<?php
namespace Milvus\SDK\Exceptions;

class CollectionException extends MilvusException
{
    public function __construct(string $message = '', int $statusCode = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}