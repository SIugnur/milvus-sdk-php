<?php
namespace Milvus\SDK\Response;

use Milvus\Proto\Milvus\MutationResult as ProtoMutationResult;

class MutationResult
{
    private ProtoMutationResult $raw;

    public function __construct(ProtoMutationResult $raw)
    {
        $this->raw = $raw;
    }

    public function getInsertCount(): int
    {
        return $this->raw->getInsertCnt();
    }

    public function getDeleteCount(): int
    {
        return $this->raw->getDeleteCnt();
    }

    public function getUpsertCount(): int
    {
        return $this->raw->getUpsertCnt();
    }

    public function getIds(): array
    {
        $ids = $this->raw->getIDs();
        if ($ids === null) {
            return [];
        }
        if ($ids->getIntId() !== null) {
            return iterator_to_array($ids->getIntId()->getData());
        }
        if ($ids->getStrId() !== null) {
            return iterator_to_array($ids->getStrId()->getData());
        }
        return [];
    }

    public function getInsertIds(): array
    {
        return $this->getIds();
    }

    public function getDeleteIds(): array
    {
        return $this->getIds();
    }

    public function getUpsertIds(): array
    {
        return $this->getIds();
    }

    public function getSuccIndex(): array
    {
        $succIndex = $this->raw->getSuccIndex();
        return $succIndex ? iterator_to_array($succIndex) : [];
    }

    public function getErrIndex(): array
    {
        $errIndex = $this->raw->getErrIndex();
        return $errIndex ? iterator_to_array($errIndex) : [];
    }

    public function getTimestamp(): int
    {
        return $this->raw->getTimestamp();
    }

    public function getRaw(): ProtoMutationResult
    {
        return $this->raw;
    }
}