<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use pocketmine\player\Player;

final class SettingVariables {

	public static function fetch(Player $player): ?PlayerConfigAdapter {
		return PlayerDataCenter::getInstance()?->get($player)?->getSetting();
	}

	const PARTICLE_PER_TICK = "ParticlePerTick";
	const DEBUG_DAMAGE = "DebugDamage";
}
