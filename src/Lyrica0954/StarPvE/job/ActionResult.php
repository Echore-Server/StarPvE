<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

class ActionResult {

    const FAILED = 0;
    const SUCCEEDED = 1;
    const SUCCEEDED_SILENT = 2;
    const ABANDONED = 3;
    const MISS = 4;

    const FAILED_BY_COOLTIME = 4;
    const FAILED_ALREADY_ACTIVE = 5;

    public static function FAILED(): self {
        return new self(self::FAILED);
    }

    public static function SUCCEEDED(): self {
        return new self(self::SUCCEEDED);
    }

    public static function SUCCEEDED_SILENT(): self {
        return new self(self::SUCCEEDED_SILENT);
    }

    public static function ABANDONED(): self {
        return new self(self::ABANDONED);
    }

    public static function FAILED_BY_COOLTIME(): self {
        return new self(self::FAILED_BY_COOLTIME);
    }

    public static function FAILED_ALREADY_ACTIVE(): self {
        return new self(self::FAILED_ALREADY_ACTIVE);
    }

    public static function MISS(): self {
        return new self(self::MISS);
    }

    public function __construct(int $result) {
        $this->result = $result;
    }

    public function getResult(): int {
        return $this->result;
    }

    public function setResult(int $result): void {
        $this->result = $result;
    }

    public function isFailed(): bool {
        return $this->result === self::FAILED;
    }

    public function isSucceeded(): bool {
        return $this->result === self::SUCCEEDED || $this->result === self::SUCCEEDED_SILENT;
    }

    public function isAbandoned(): bool {
        return $this->result === self::ABANDONED;
    }

    public function isFailedByCooltime(): bool {
        return $this->result === self::FAILED_BY_COOLTIME;
    }

    public function isFailedAlreadyActive(): bool {
        return $this->result === self::FAILED_ALREADY_ACTIVE;
    }

    public function isMiss(): bool {
        return $this->result === self::MISS;
    }
}
