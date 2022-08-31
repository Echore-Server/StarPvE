<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player\adapter;

use Lyrica0954\PeekAntiCheat\profile\Client;
use Lyrica0954\PeekAntiCheat\punish\ban\BanManager;
use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\adapter\SimpleConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerConfig;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\event\global\GlobalAddExpEvent;
use Lyrica0954\StarPvE\event\global\GlobalLevelupEvent;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class GenericConfigAdapter extends PlayerConfigAdapter {

	public static function getExpToCompleteLevel(int $level) {
		$exp = pow($level, 2) * 4 + 10;

		return $exp;
	}

	public static function fetch(Player $player): ?GenericConfigAdapter {
		return PlayerDataCenter::getInstance()?->get($player)?->getGeneric();
	}

	const GAME_WON = "GameWon";
	const GAME_LOST = "GameLost";
	const MONSTER_KILLS = "MonsterKills";
	const DEATHS = "Deaths";
	const PLAY_COUNT = "PlayCount";
	const LEVEL = "Level";
	const EXP = "Exp";
	const TOTAL_EXP = "TotalExp";
	const NEXT_EXP = "NextExp";

	const USERNAME = "Username";
	const FIRST_PLAYED = "FirstPlayed";
	const LAST_PLAYED = "LastPlayed";

	const PERMS = "Perms";
	const RANKS = "Ranks";
	const WARN = "Warn";

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

	public function warn(Player $player, int $amount = 1): void {
		if ($player->getXuid() !== $this->xuid) {
			return;
		}

		$current = $this->addInt(self::WARN, $amount);
		if ($current >= 4) {
			BanManager::getInstance()->addBan(Client::createFromPlayer($player), null, "警告レベルが最大に達したため", 0);
			$player->kick("Custom disconnect");
		} elseif ($amount > 0) {
			$player->sendMessage("



























§c§l警告が付与されました。 警告レベル: {$current}
");
			TaskUtil::repeatingClosureLimit(function () use ($player) {
				PlayerUtil::playSound($player, "note.pling", 1.2, 0.2);
			}, 1, 40);

			TaskUtil::delayed(new ClosureTask(function () use ($player) {
				PlayerUtil::playSound($player, "item.trident.thunder", 0.6, 0.5);
			}), 50);
		}
	}
}
