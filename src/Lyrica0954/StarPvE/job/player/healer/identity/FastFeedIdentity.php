<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer\identity;

use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;

class FastFeedIdentity extends Identity{

	protected int $period;
	protected ?TaskHandler $taskHandler;

	public function __construct(int $period){
		parent::__construct();
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

	public function apply(Player $player): void{
		$this->reset($player);
		
		$this->taskHandler = TaskUtil::repeatingClosure(function() use($player){
			$hunger = $player->getHungerManager();
			$food = $hunger->getFood();
			if ($food >= $hunger->getMaxFood()){
				$regain = new EntityRegainHealthEvent($player, 1, EntityRegainHealthEvent::CAUSE_CUSTOM);
				$player->heal($regain);
			}
		}, $this->period);
	}

	public function reset(Player $player): void{
		$this->taskHandler?->cancel();
	}
}