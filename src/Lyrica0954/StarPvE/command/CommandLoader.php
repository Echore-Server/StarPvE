<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\JobInformationForm;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\permission\DefaultPermissions;

class CommandLoader {

	public static function load(StarPvE $p) {
		$clear = $p->getServer()->getCommandMap()->getCommand("clear");
		$clear?->setPermission(DefaultPermissions::ROOT_OPERATOR);

		$kill = $p->getServer()->getCommandMap()->getCommand("kill");
		$kill?->setPermission(DefaultPermissions::ROOT_OPERATOR);

		new HubCommand("hub", $p, $p);
		new GameCommand("game", $p, $p);
		new JobInfoCommand("jobstats", $p, $p);
		new TestCommand("testf", $p, $p);
		new PlayerStatusCommand("stats", $p, $p);
		new SettingCommand("setting", $p, $p);

		new TaskInfoCommand("taskinfo", $p, $p);
	}
}
