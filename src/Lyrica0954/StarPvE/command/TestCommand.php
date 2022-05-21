<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\data\inventory\LockedVirtualInventory;
use Lyrica0954\StarPvE\data\inventory\ReadOnlyVirtualInventory;
use Lyrica0954\StarPvE\data\inventory\SourcedVirtualInventory;
use Lyrica0954\StarPvE\data\inventory\VirtualBlock;
use Lyrica0954\StarPvE\data\inventory\VirtualInventory;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

final class TestCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER;
	}

	protected function run(CommandSender $sender, array $args): void {
		if ($sender instanceof Player) {
			$virtual = new LockedVirtualInventory($sender, VirtualInventory::CHEST_LARGE_SIZE, "New Inventory");
			$virtual->setContents(
				array_fill(0, VirtualInventory::CHEST_LARGE_SIZE / 2, VanillaItems::APPLE()->setCount(1)->setLore(["§rSpecial Apple!"]))
			);
			$virtual->open();
		}
	}
}
