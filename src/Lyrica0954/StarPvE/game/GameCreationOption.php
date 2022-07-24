<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

use Lyrica0954\StarPvE\game\stage\StageFactory;

class GameCreationOption {

    protected string $id;

    protected string $stageName;

    protected int $maxPlayers;

    public static function genId(int $length): string {
        return substr(str_shuffle("qwertyuiopasdfghjklzxcvbnm1234567890"), 0, $length);
    }

    public static function manual(int $maxPlayers = 6, ?string $stageName = null, ?string $id = null): self {
        $stageNames = array_keys(StageFactory::getInstance()->getList());
        $stageName = $stageName ?? $stageNames[array_rand($stageNames)];
        return new self($id ?? self::genId(10), $stageName, $maxPlayers);
    }


    public function __construct(string $id, string $stageName, int $maxPlayers) {
        $this->id = $id;
        $this->stageName = $stageName;
        $this->maxPlayers = $maxPlayers;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getStageName(): string {
        return $this->stageName;
    }

    public function getMaxPlayers(): int {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): void {
        $this->maxPlayers = $maxPlayers;
    }
}
