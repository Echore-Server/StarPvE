<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player\adapter;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use pocketmine\player\Player;

class JobConfigAdapter extends PlayerConfigAdapter{

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

	public static function getExpToCompleteLevel(int $level){
        $jobExp = pow($level, 3) * 4 + ($level * 20);

        return $jobExp;
    }

	public static function fetch(Player $player): ?JobConfigAdapter{
        return PlayerDataCenter::getInstance()?->get($player)?->getJob();
    }

	public function createEntry(string $name): array{
		$entry = [
			self::NAME => $name,
			self::LEVEL => 1,
			self::EXP => 0,
			self::TOTAL_EXP => 0,
			self::NEXT_EXP => self::getExpToCompleteLevel(1),
			self::GAME_WON => 0,
			self::GAME_LOST => 0,
			self::MONSTER_KILLS => 0,
			self::DEATHS => 0,
			self::PLAY_COUNT => 0
		];
		$this->getConfig()->setNested($name, $entry);
		return $entry;
	}

	public function setEntry(string $name, array $entry): array{
		$old = $this->getEntry($name);
		if ($old == null){
			throw new \Exception("entry \"{$name}\" not found");
		}

		$this->getConfig()->setNested($name, $entry);
		return $old;
	}

	public function changeEntry(string $name, mixed $entryKey, mixed $entryValue): mixed{
		$entry = $this->getEntry($name);
		if ($entry == null){
			throw new \Exception("entry \"{$name}\" not found");
		}

		if (!isset($entry[$entryKey])){
			throw new \Exception("entry key \"{$entryKey}\" not found");
		}

		$new = $entry;
		$new[$entryKey] = $entryValue;
		$this->setEntry($name, $new);
		return $entry[$entryKey];
	}

	public function getEntry(string $name): ?array{
		$nested = $this->getConfig()->getNested($name, null);
		if (!is_array($nested)){
			$nested = null;
		}

		return $nested;
	}
}