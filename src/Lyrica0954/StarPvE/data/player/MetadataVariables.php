<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use pocketmine\player\Player;

class MetadataVariables {

	public static function fetch(Player $player): ?PlayerConfigAdapter {
		return PlayerDataCenter::getInstance()?->get($player)?->getMetadata();
	}

	const PERMS = "Perms";
	const RANKS = "Ranks";
}
