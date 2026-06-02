<?php
namespace Milvus\SDK\Exceptions;

class ParamException extends MilvusException
{
    public function __construct(string $message = 'Invalid parameter', ?\Throwable $previous = null)
    {
        parent::__construct($message, 2, $previous);
    }
}