<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use Exception;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\event\HandlerListManager;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\SimpleInventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Utils;
use pocketmine\world\Position;

abstract class VirtualInventory extends SimpleInventory implements BlockInventory, Listener {

	const CHEST_SIZE = 27;
	const CHEST_LARGE_SIZE = 27 * 2;

	protected string $title;

	protected Position $holder;

	/**
	 * @var Player
	 */
	protected Player $player;

	/**
	 * @var Vector3[]
	 */
	protected array $virtualBlock;

	/**
	 * @var int
	 */
	protected int $size;

	/**
	 * @var bool
	 */
	protected bool $opened;

	/**
	 * @var bool
	 */
	private bool $prepareOpen;

	public function __construct(Player $player, int $size = self::CHEST_SIZE, string $title = "Virtual Inventory") {
		assert(
			$size == self::CHEST_SIZE || $size == self::CHEST_LARGE_SIZE,
			new Exception("size must be chest size")
		);
		parent::__construct($size);

		$this->setTitle($title);
		$this->holder = new Position(0, 0, 0, null);
		$this->player = $player;
		$this->virtualBlock = [];
		$this->size = $size;
		$this->prepareOpen = false;
		$this->opened = false;

		Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}

	public function __destruct() {
		HandlerListManager::global()->unregisterAll($this);
	}

	public function isOpened(): bool {
		return $this->opened;
	}

	public function onInventoryTransaction(InventoryTransactionEvent $event): void {
		$trans = $event->getTransaction();
		$player = $trans->getSource();
		foreach ($trans->getInventories() as $inventory) {
			if ($inventory instanceof VirtualInventory) {
				foreach ($trans->getActions() as $action) {
					if ($action instanceof SlotChangeAction) {
						$result = $this->onSlotChangeAction($action->getSlot());
						if (!$result) {
							$event->cancel();
						}
					}

					if ($action instanceof DropItemAction) {
						$item = $action->getTargetItem();
						$result = $this->onDropAction($item);
						if (!$result) {
							$event->cancel();
						}
					}
				}
			}
		}
	}

	abstract protected function onSlotChangeAction(int $slot): bool;

	abstract protected function onDropAction(Item $item): bool;

	/**
	 * @return Vector3[]
	 */
	public function getVirtualBlock(): array {
		return $this->virtualBlock;
	}

	public function unselectItem(): void {
		$pk = InventorySlotPacket::create(ContainerIds::UI, 0, ItemStackWrapper::legacy(ItemStack::null()));
		$this->player->getNetworkSession()->sendDataPacket($pk);
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function setTitle(string $title): void {
		Utils::checkUTF8($title);
		$this->title = $title;
	}

	public function getHolder(): Position {
		return $this->holder;
	}

	public function open(): void {
		$this->prepareOpen = true;
		$pos = $this->player->getPosition()->add(0, 2 + $this->player->getEyeHeight(), 0);
		$this->holder = VectorUtil::insertWorld($pos->floor(), $this->player->getWorld());
		$virtualBlock = VirtualBlock::sendChest($this->player, $pos, $this->title, ($this->size == self::CHEST_LARGE_SIZE));
		$this->virtualBlock = array_merge($this->virtualBlock, $virtualBlock);
		TaskUtil::delayed(new ClosureTask(function () {
			if ($this->prepareOpen) {
				$this->player->setCurrentWindow($this);
				$this->opened = true;
				$this->prepareOpen = false;
			} else {
				$this->onClose($this->player);
			}
		}), 4);
	}

	public function close(): void {
		if ($this->opened) {
			$this->player->removeCurrentWindow();
		} elseif ($this->prepareOpen) {
			$this->prepareOpen = false;
		}
	}

	public function onClose(Player $who): void {
		assert($this->player === $who, new \Exception("does not match player === who"));
		foreach ($this->virtualBlock as $pos) {
			$pk = VirtualBlock::getUpdateBlockPacket($pos, $who->getWorld()->getBlock($pos));
			$who->getNetworkSession()->sendDataPacket($pk, true);
		}


		$this->virtualBlock = [];

		parent::onClose($who);
	}

	public static function fromDataInventory(SimpleDataInventory $inventory, VirtualInventory $core): VirtualInventory {
		$contents = $inventory->getContents(true);
		$coreContents = [];

		foreach ($contents as $i => $invItem) {
			$item = $invItem->createEntryItem();
			$coreContents[$i] = $item;
		}


		$core->setContents($coreContents);
		return $core;
	}
}
