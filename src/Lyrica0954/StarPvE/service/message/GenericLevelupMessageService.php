<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\message;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\event\global\GlobalAddExpEvent;
use Lyrica0954\StarPvE\event\global\GlobalLevelupEvent;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

class GenericLevelupMessageService extends ListenerService {

	protected array $previous;

	protected function init(): void{
		parent::init();
		
		$this->previous = [];
	}

	/**
	 * @param GlobalAddExpEvent $event
	 * 
	 * @return void
	 */
	public function onGlobalAddExp(GlobalAddExpEvent $event): void{
		$adapter = $event->getAdapter();

		if ($adapter instanceof GenericConfigAdapter){
			$player = PlayerUtil::searchByXuid($adapter->getXuid());
			if ($player instanceof Player){
				$selectableJobs = StarPvE::getInstance()->getJobManager()->getSelectableJobs($player);
				$this->previous[spl_object_hash($player)] = $selectableJobs;
			}
		}
	}

	/**
	 * @param GlobalLevelupEvent $event
	 * 
	 * @return void
	 */
	public function onGlobalLevelup(GlobalLevelupEvent $event): void{
		$adapter = $event->getAdapter();
		if ($adapter instanceof GenericConfigAdapter){
			$player = PlayerUtil::searchByXuid($adapter->getXuid());
			if ($player instanceof Player){
				$currentSelectableJobs = StarPvE::getInstance()->getJobManager()->getSelectableJobs($player);
				$newSelectableJobs = array_diff($currentSelectableJobs, $this->previous[spl_object_hash($player)] ?? []);
				$exp = $adapter->getConfig()->get(GenericConfigAdapter::EXP, 0);
				$nextExp = $adapter->getConfig()->get(GenericConfigAdapter::NEXT_EXP, 0);
				$player->sendMessage("§a------ §lLevel Up!§r §a------");
				$player->sendMessage("§6> レベル: §a{$event->getOld()} >> {$event->getNew()}");
				$player->sendMessage("§6> 次のExp: §a{$exp}§f/§a{$nextExp}");
				$player->sendMessage("§a-----------------------");
	
				foreach($newSelectableJobs as $class){
					$job = new $class(null);
					if ($job instanceof Job){
						$player->sendMessage("§d> §d{$job->getName()} §7がアンロックされました！");
					}
				}
				PlayerUtil::playSound($player, "random.levelup", 1.0, 0.5);
			}
		}
	}
}