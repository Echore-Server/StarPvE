<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use Generator;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

class PlayerUtil {

    public static function playSound(Player $player, string $name, float $pitch = 1.0, float $volume = 1.0) {
        $pk = self::getSoundPacket($name, $pitch, $volume, $player->getPosition());
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public static function broadcastSound(Entity|Position $pos, string $name, float $pitch = 1.0, float $volume = 1.0) {
        $vec3 = ($pos instanceof Entity ? $pos->getPosition() : $pos);
        $pk = self::getSoundPacket($name, $pitch, $volume, $vec3);
        $pos->getWorld()->broadcastPacketToViewers($vec3, $pk);
    }

    public static function getSoundPacket(string $name, float $pitch = 1.0, float $volume = 1.0, Vector3 $pos) {
        $pk = new PlaySoundPacket();

        $pk->x = $pos->x;
        $pk->y = $pos->y;
        $pk->z = $pos->z;

        $pk->pitch = $pitch;
        $pk->volume = $volume;
        $pk->soundName = $name;

        return $pk;
    }

    public static function findSword(Player $player): ?Sword {
        foreach ($player->getInventory()->getContents() as $item) {
            if ($item instanceof Sword) {
                return $item;
            }
        }

        return null;
    }

    public static function findSwordIndex(Player $player): ?int {
        foreach ($player->getInventory()->getContents() as $index => $item) {
            if ($item instanceof Sword) {
                return $index;
            }
        }

        return null;
    }

    public static function countItem(Player $player, int $id): int {
        $has = 0;
        foreach ($player->getInventory()->getContents() as $item) {
            if ($id === $item->getId()) {
                $has += $item->getCount();
            }
        }
        return $has;
    }

    public static function give(Player $player, Item $item): void {
        if ($player->getInventory()->canAddItem($item)) {
            $player->getInventory()->addItem($item);
        }
    }


    public static function flee(Player $player) {
        $player->extinguish();
        $player->getEffects()->clear();
        $player->getHungerManager()->setSaturation(20);
        $player->getHungerManager()->setFood(20);
        $player->setHealth($player->getMaxHealth());
    }

    public static function reset(Player $player) {
        self::flee($player);
        $player->getArmorInventory()->clearAll();
        $player->getInventory()->clearAll();
        $player->getCraftingGrid()->clearAll();
    }

    public static function teleportToLobby(Player $player) {
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(ItemIds::COMPASS));
        $player->teleport(new Position(0, 51, 0, StarPvE::getInstance()->hub));
    }

    public static function searchByXuid(string $xuid): Player {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player->getXuid() == $xuid) {
                return $player;
            }
        }
    }
}
