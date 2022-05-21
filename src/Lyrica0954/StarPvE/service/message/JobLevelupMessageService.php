<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\message;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\event\global\GlobalAddExpEvent;
use Lyrica0954\StarPvE\event\global\GlobalLevelupEvent;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

class JobLevelupMessageService extends ListenerService {

	protected array $previous;

	protected function init(): void {
		parent::init();

		$this->previous = [];
	}

	/**
	 * @param GlobalAddExpEvent $event
	 * 
	 * @return void
	 */
	public function onGlobalAddExp(GlobalAddExpEvent $event): void {
		$adapter = $event->getAdapter();

		if ($adapter instanceof JobConfigAdapter) {
			$player = PlayerUtil::searchByXuid($adapter->getXuid());
			if ($player instanceof Player) {
				$job = StarPvE::getInstance()->getJobManager()->get($adapter->getConfig()->get(JobConfigAdapter::NAME));
				if ($job !== null) {
					$jobInstance = new $job(null);
					/**
					 * @var PlayerJob $jobInstance
					 */

					$list = $jobInstance->getIdentityGroup()->getActive($player);
					$l2 = [];
					foreach ($list as $identity) {
						$l2[] = $identity->getName();
					}
					$this->previous[spl_object_hash($player)] = $l2;
				}
			}
		}
	}

	/**
	 * @param GlobalLevelupEvent $event
	 * 
	 * @return void
	 */
	public function onGlobalLevelup(GlobalLevelupEvent $event): void {
		$adapter = $event->getAdapter();
		if ($adapter instanceof JobConfigAdapter) {
			$player = PlayerUtil::searchByXuid($adapter->getXuid());
			if ($player instanceof Player) {
				$job = StarPvE::getInstance()->getJobManager()->get($adapter->getConfig()->get(JobConfigAdapter::NAME));
				if ($job !== null) {
					$jobInstance = new $job(null);
					/**
					 * @var PlayerJob $jobInstance
					 */


					$exp = $adapter->getConfig()->get(JobConfigAdapter::EXP, 0);
					$nextExp = $adapter->getConfig()->get("NextExp", 0);
					$player->sendMessage("§a------ §d§lLevel Up!§r §a------");
					$player->sendMessage("§d職業 {$jobInstance->getName()} のレベルが上昇しました！");
					$player->sendMessage("§6> レベル: §a{$event->getOld()} >> {$event->getNew()}");
					$player->sendMessage("§6> 次のExp: §a{$exp}§f/§a{$nextExp}");
					$player->sendMessage("§a-----------------------");

					$list = $jobInstance->getIdentityGroup()->getActive($player);
					$active = [];
					foreach ($list as $identity) {
						$active[] = $identity->getName();
					}
					$newIdentity = array_diff($active, $this->previous[spl_object_hash($player)] ?? []);
					foreach ($newIdentity as $identity) {
						$player->sendMessage("§d> §d{$identity} §7がアンロックされました！");
					}
					PlayerUtil::playSound($player, "item.trident.return", 1.0, 0.75);
					PlayerUtil::playSound($player, "random.totem", 1.2, 0.25);
				}
			}
		}
	}
}
