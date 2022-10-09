<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\form\YesNoForm;
use Lyrica0954\StarPvE\utils\TaskUtil;
use NeiroNetwork\VanillaCommands\parameter\BasicParameters;
use NeiroNetwork\VanillaCommands\parameter\Parameter;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\Server;

final class WarnCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER | self::CONSOLE;
	}

	protected function init(): void {
		$this->setDescription("プレイヤーを警告");
		$this->setPermission(PermissionNames::COMMAND_WARN);
	}

	protected function initParameter(): void {
		Parameter::getInstance()->add("warn", [
			BasicParameters::targets("victim"),
			BasicParameters::int("amount", optional: true)
		]);
	}

	protected function run(CommandSender $sender, array $args): void {
		if (count($args) > 0) {
			$victimName = $args[0];
			$victim = Server::getInstance()->getPlayerByPrefix($victimName);
			if ($victim instanceof Player) {
				$amount = (int) ($args[1] ?? 1);
				if ($amount <= 0) {
					$sender->sendMessage("§camount は1以上である必要があります");
					return;
				}
				$warn = function (Player $player) use ($sender, $amount): void {
					$adapter = GenericConfigAdapter::fetch($player);
					if ($adapter instanceof GenericConfigAdapter) {
						$adapter->warn($player, $amount);
						$sender->sendMessage("§c警告しました。");
					}
				};

				if ($sender instanceof Player) {

					$confirmForm = new YesNoForm("§f本当に §b{$victim->getName()} §fを警告しますか？\n§f警告レベル +{$amount}", function (Player $player, $data) use ($victim, $amount, $warn) {
						if ($data === 0) {
							$warn($victim);
						}
					});
					$sender->sendForm($confirmForm);
				} else {
					$warn($victim);
				}
			} else {
				$sender->sendMessage("§cプレイヤーが見つかりません");
			}
		}
	}
}
