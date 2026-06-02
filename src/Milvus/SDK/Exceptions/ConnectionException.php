<?php
namespace Milvus\SDK\Exceptions;

class ConnectionException extends MilvusException
{
    public function __construct(string $message = 'Failed to connect to Milvus', ?\Throwable $previous = null)
    {
        parent::__construct($message, 1, $previous);
    }
}