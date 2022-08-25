<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\command\SettingForm;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class SettingCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER;
	}


	protected function run(CommandSender $sender, array $args): void {
		if ($sender instanceof Player) {
			$form = new SettingForm($sender);
			$sender->sendForm($form);
		}
	}
}
