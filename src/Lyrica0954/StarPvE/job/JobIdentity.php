<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\ConditionTrait;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class JobIdentity extends Identity {
    use ConditionTrait;

    protected PlayerJob $playerJob;

    public function __construct(PlayerJob $playerJob, ?Condition $condition) {
        $this->playerJob = $playerJob;
        $this->setCondition($condition);
        parent::__construct();
    }

    public function isApplicable(): bool {
        $player = $this->playerJob->getPlayer();

        $result = true;
        if ($player instanceof Player) {
            $result = $this->getCondition()?->check($player) ?? true;
        }

        return $result;
    }

    public function isApplicableFor(Player $player): bool {
        return $this->condition?->check($player) ?? true;
    }
}
