<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\data\inventory\DataInventoryInstance;
use Lyrica0954\StarPvE\data\inventory\item\InvItemFactory;
use Lyrica0954\StarPvE\data\inventory\item\InvItemIds;
use Lyrica0954\StarPvE\data\inventory\item\MaterialItem;
use Lyrica0954\StarPvE\data\inventory\LockedVirtualInventory;
use Lyrica0954\StarPvE\data\inventory\ReadOnlyVirtualInventory;
use Lyrica0954\StarPvE\data\inventory\SimpleDataInventory;
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

	/**
	 * @var DataInventoryInstance[]
	 */
	protected array $instances;

	public function canRunBy(): int {
		return self::PLAYER;
	}

	protected function run(CommandSender $sender, array $args): void {
		if ($sender instanceof Player) {
			if (!isset($this->instances[$sender->getXuid()])) {
				$dataInventory = new SimpleDataInventory(VirtualInventory::CHEST_LARGE_SIZE);
				$item = InvItemFactory::getInstance()->get(InvItemIds::APPLE);
				$item->setDescription("すごい！");
				$dataInventory->setContents(
					array_fill(0, VirtualInventory::CHEST_LARGE_SIZE / 2, clone $item)
				);
				$this->instances[$sender->getXuid()] = new DataInventoryInstance($sender, $dataInventory, "New Inventory");
			}

			$instance = $this->instances[$sender->getXuid()];
			$instance->open();
		}
	}
}
