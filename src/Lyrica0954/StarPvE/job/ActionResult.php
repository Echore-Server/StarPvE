<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

class ActionResult {

    const FAILED = 0;
    const SUCCEEDED = 1;
    const ABANDONED = 2;

    const FAILED_BY_COOLTIME = 3;

    public function __construct(int $result){
        $this->result = $result;
    }

    public function getResult(): int{
        return $this->result;
    }

    public function setResult(int $result): void{
        $this->result = $result;
    }

    public function isFailed(): bool{
        return $this->result === self::FAILED;
    }

    public function isSucceeded(): bool{
        return $this->result === self::SUCCEEDED;
    }

    public function isAbandoned(): bool{
        return $this->result === self::ABANDONED;
    }

    public function isFailedByCooltime(): bool{
        return $this->result === self::FAILED_BY_COOLTIME;
    }

}