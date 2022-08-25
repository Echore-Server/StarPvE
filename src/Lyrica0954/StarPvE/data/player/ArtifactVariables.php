<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use Lyrica0954\StarPvE\data\player\adapter\ItemConfigAdapter;
use pocketmine\player\Player;

class ArtifactVariables {

	public static function fetch(Player $player): ?ItemConfigAdapter {
		return PlayerDataCenter::getInstance()?->get($player)?->getArtifact();
	}
}
