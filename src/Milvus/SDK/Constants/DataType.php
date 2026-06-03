<?php
namespace Milvus\SDK\Constants;

final class DataType
{
    const None = 0;
    const Bool = 1;
    const Int8 = 2;
    const Int16 = 3;
    const Int32 = 4;
    const Int64 = 5;
    const Float = 10;
    const Double = 11;
    const String = 20;
    const VarChar = 21;
    const Array = 22;
    const JSON = 23;
    const BinaryVector = 100;
    const FloatVector = 101;
    const Float16Vector = 102;
    const BFloat16Vector = 103;
    const SparseFloatVector = 104;
    const Int8Vector = 105;
}