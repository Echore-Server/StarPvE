<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\StatusForm;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class PlayerStatusCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER;
	}

	protected function init(): void {
		$this->setAliases([
			"status",
			"stat",
			"stats"
		]);

		$this->setDescription("ステータスの確認");
	}

	protected function run(CommandSender $sender, array $args): void {
		if ($sender instanceof Player) {
			$form = new StatusForm($sender);
			$sender->sendForm($form);
		}
	}
}
