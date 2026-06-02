<?php
namespace Milvus\SDK\Constants;

final class ConsistencyLevel
{
    const Strong = 0;
    const Session = 1;
    const Bounded = 2;
    const Eventually = 3;
    const Customized = 4;
}