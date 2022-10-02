<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\help\HelpForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class HelpCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER;
	}

	protected function run(CommandSender $sender, array $args): void {

		if ($sender instanceof Player) {
			$form = new HelpForm;
			$sender->sendForm($form);
		}
	}
}
