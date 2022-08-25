<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\condition;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\data\player\PlayerDataCollector;
use pocketmine\player\Player;

class FalseCondition implements Condition {

	public function __construct() {
	}

	public function check(Player $player): bool {
		return false;
	}

	public function asText(): string {
		return "選択/有効不可";
	}
}
