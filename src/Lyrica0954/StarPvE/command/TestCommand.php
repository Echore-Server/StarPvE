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
use Lyrica0954\StarPvE\data\player\adapter\ItemConfigAdapter;
use Lyrica0954\StarPvE\data\player\BagVariables;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
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

	protected function init(): void {
		$this->setDescription("バッグ");
		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	protected function run(CommandSender $sender, array $args): void {
		if ($sender instanceof Player) {

			$bag = BagVariables::fetch($sender);
			if ($bag instanceof ItemConfigAdapter) {
				$dataInventory = $bag->getInventory();



				if (!isset($this->instances[$sender->getXuid()])) {
					$virtualInventory = new LockedVirtualInventory($sender, VirtualInventory::CHEST_LARGE_SIZE, "Super Strong Beatufiul Mega Inventory");
					$this->instances[$sender->getXuid()] = new DataInventoryInstance($sender, $dataInventory, $virtualInventory);
				}

				$instance = $this->instances[$sender->getXuid()];
				$instance->open();
			}
		}
	}
}
