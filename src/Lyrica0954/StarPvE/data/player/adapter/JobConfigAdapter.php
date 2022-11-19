<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player\adapter;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\event\global\GlobalAddExpEvent;
use Lyrica0954\StarPvE\event\global\GlobalLevelupEvent;
use Lyrica0954\StarPvE\job\JobManager;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\player\Player;

class JobConfigAdapter extends PlayerConfigAdapter {

	const NAME = "EntryName";
	const LEVEL = "Level";
	const EXP = "Exp";
	const TOTAL_EXP = "TotalExp";
	const NEXT_EXP = "NextExp";

	const GAME_WON = "GameWon";
	const GAME_LOST = "GameLost";
	const MONSTER_KILLS = "MonsterKills";
	const DEATHS = "Deaths";
	const PLAY_COUNT = "PlayCount";

	public static function getExpToCompleteLevel(int $level) {
		$exp = ($level ** 3) + 10;

		$diffusion = floor((1 + floor($level / 10)) * floor($level / 5)) ** 3;

		return $exp + $diffusion;
	}

	public static function fetch(Player $player, string $name): ?JobConfigAdapter {
		return PlayerDataCenter::getInstance()?->get($player)?->getJob($name);
	}

	public static function fetchCurrent(Player $player): ?JobConfigAdapter {
		$job = StarPvE::getInstance()->getJobManager()->getJob($player);
		if ($job !== null) {
			return self::fetch($player, ((new \ReflectionClass($job))->getShortName()));
		} else {
			return null;
		}
	}

	public function addExp(float $amount): mixed {
		$eev = new GlobalAddExpEvent($this, $amount);
		$eev->call();

		if ($eev->isCancelled()) {
			return $this->getConfig()->get(self::EXP);
		}

		$exp = $this->addFloat(self::EXP, $amount);
		$this->addFloat(self::TOTAL_EXP, $amount);
		$nextExp = $this->getConfig()->get(self::NEXT_EXP);
		$newExp = $exp;
		if ($exp >= $nextExp) {
			$level = $this->addInt(self::LEVEL, 1);
			$newNextExp = self::getExpToCompleteLevel((int) $level);
			$this->getConfig()->set(self::NEXT_EXP, $newNextExp);
			$over = ($exp - $nextExp);
			$this->getConfig()->set(self::EXP, 0);
			$newExp = 0;
			if ($over > 0) $newExp = $this->addExp($over);


			$ev = new GlobalLevelupEvent($this, $level - 1, $level);
			$ev->call();
		}

		return $newExp;
	}
}
