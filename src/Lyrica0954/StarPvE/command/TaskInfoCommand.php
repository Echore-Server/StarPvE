<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\StatusForm;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;


final class TaskInfoCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER | self::CONSOLE;
	}

	protected function init(): void {

		$this->setDescription("実行中タスクの確認");
		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	protected function run(CommandSender $sender, array $args): void {
		$runningHandlers = TaskUtil::getRunning();
		$handled = count(TaskUtil::getHandled());

		$running = count($runningHandlers);

		$repeating = 0;
		$delayed = 0;
		foreach ($runningHandlers as $handler) {
			if ($handler->isRepeating()) {
				$repeating++;
			} elseif ($handler->isDelayed()) {
				$delayed++;
			}
		}

		$sender->sendMessage("Running: §a{$running}§f\nHandled(Total): §a{$handled}§f\nDelayed: §a{$delayed}§f\nRepeating: §a{$repeating}§f");
	}
}
