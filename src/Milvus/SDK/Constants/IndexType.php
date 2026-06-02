<?php
namespace Milvus\SDK\Constants;

final class IndexType
{
    const INVALID = 0;
    const FLAT = 1;
    const IVFFLAT = 2;
    const IVFSQ8 = 3;
    const IVFPQ = 4;
    const HNSW = 5;
    const DISKANN = 10;
    const AUTOINDEX = 50;
    const GPUIVFFLAT = 55;
    const GPUIVFSQ8 = 56;
    const BINFLAT = 80;
    const BINIVFFLAT = 81;
    const TANTAMI = 90;
    const SPARSEINVERTEDINDEX = 100;
    const SPARSEWAND = 101;
}