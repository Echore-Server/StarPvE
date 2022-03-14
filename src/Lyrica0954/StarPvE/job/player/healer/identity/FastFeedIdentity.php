<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer\identity;

use Lyrica0954\StarPvE\job\Identity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;

class FastFeedIdentity extends Identity{

	protected int $period;
	protected ?TaskHandler $taskHandler;

	public function __construct(PlayerJob $playerJob, int $period){
		parent::__construct($playerJob);
		$this->period = max(1, $period);
		$this->taskHandler = null;
	}

	public function getName(): string{
		return "高速回復";
	}

	public function getDescription(): string{
		$sec = round($this->period / 20, 1);
		return "おなか一杯の時、体力が {$sec}秒 に一回回復する";
	}

	public function isActivateable(): bool{
		if (!$this->playerJob->getPlayer() instanceof Player){
            return false;
        }

		return parent::isActivateable();
	}

	public function apply(): void{
		$this->taskHandler = TaskUtil::repeatingClosure(function(){
			$player = $this->playerJob->getPlayer();
			$hunger = $player->getHungerManager();
			$food = $hunger->getFood();
			if ($food >= $hunger->getMaxFood()){
				$regain = new EntityRegainHealthEvent($player, 1, EntityRegainHealthEvent::CAUSE_CUSTOM);
				$player->heal($regain);
			}
		}, $this->period);
	}

	public function reset(): void{
		$this->taskHandler?->cancel();
	}
}