<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use Lyrica0954\StarPvE\service\PlayerCounterService;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\player\Player;

class DataInventoryInstance {

    protected Player $player;

    protected BaseDataInventory $dataInventory;

    protected VirtualInventory $virtualInventory;

    public function __construct(Player $player, BaseDataInventory $dataInventory, string $title) {
        $this->player = $player;

        $this->dataInventory = $dataInventory;

        $this->virtualInventory = new LockedVirtualInventory($player, VirtualInventory::CHEST_LARGE_SIZE, $title);
        $this->virtualInventory->closeListeners[spl_object_hash($this)] = function (Player $player): void {
            $this->onClose($player);
        };
        #$this->virtualInventory->rawListeners[spl_object_hash($this)] = function (InventoryAction $action): void {
        #    if ($action instanceof SlotChangeAction) {
        #        print_r("from: {$action->getSourceItem()->getName()}\n");
        #        print_r("to: {$action->getTargetItem()->getName()}\n");
        #        $src = $action->getSourceItem();
        #        $tar = $action->getTargetItem();
        #        $inv = $action->getInventory();
        #        if ($inv instanceof PlayerCursorInventory) {
        #            if ($src->equals($tar, true, false)) {
        #                $count = $tar->getCount() + $src->getCount();
        #                $remain = $count - 64;
        #                if ($remain > 0) {
        #                    $src->setCount($remain);
        #                } else {
        #                    $src->setCount(0);
        #                }
        #                $tar->setCount(min(64, $count));
        #                foreach ($inv->getViewers() as $viewer) {
        #                    $viewer->getNetworkSession()->getInvManager()->syncAll();
        #                }
        #            }
        #        }
        #    }
        #};
    }

    public function open(): void {
        $this->syncVirtualInventory();
        $this->virtualInventory->open();
    }

    public function onClose(Player $player): void {
        if ($player === $this->player) {
            $this->syncDataInventory();
        }
    }

    public function close(): void {
        $this->virtualInventory->close();
    }

    public function getDataInventory(): BaseDataInventory {
        return $this->dataInventory;
    }

    public function getVirtualInventory(): VirtualInventory {
        return $this->virtualInventory;
    }

    public function killInstance(): void {
        unset($this->virtualInventory->closeListeners[spl_object_hash($this)]);
    }

    public function syncDataInventory(): void {
        $new = [];
        foreach ($this->virtualInventory->getContents() as $index => $entry) {

            $hostIndex = $entry->getCustomBlockData()->getTag("hostInventoryIndex")?->getValue() ?? -1;

            if ($hostIndex !== -1) {
                $item = $this->dataInventory->getItem($hostIndex);
                $new[$index] = $item;
            }
        }

        $this->dataInventory->setContents($new);
    }

    public function syncVirtualInventory(): void {
        $this->dataInventory->injectToInventory($this->virtualInventory);
    }
}
