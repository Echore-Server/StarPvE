<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer\identity;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\player\PlayerIdentity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;

class FastFeedIdentity extends PlayerIdentity {

	protected int $period;
	protected ?TaskHandler $taskHandler;

	public function __construct(Player $player, ?Condition $condition = null, int $period) {
		parent::__construct($player, $condition);
		$this->period = max(1, $period);
		$this->taskHandler = null;
	}

	public function getName(): string {
		return "高速回復";
	}

	public function getDescription(): string {
		$sec = round($this->period / 20, 1);
		return "おなか一杯の時、体力が {$sec}秒 に一回回復する";
	}

	public function apply(): void {
		$this->reset();

		$this->taskHandler = TaskUtil::repeatingClosure(function () {
			$hunger = $this->player->getHungerManager();
			$food = $hunger->getFood();
			if ($food >= $hunger->getMaxFood()) {
				$regain = new EntityRegainHealthEvent($this->player, 1, EntityRegainHealthEvent::CAUSE_CUSTOM);
				$this->player->heal($regain);
			}
		}, $this->period);
	}

	public function reset(): void {
		$this->taskHandler?->cancel();
	}
}
