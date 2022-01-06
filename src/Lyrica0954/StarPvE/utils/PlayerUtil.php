<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\world\Position;

class PlayerUtil {

    public static function playSound(Player $player, string $name, float $pitch = 1.0, float $volume = 1.0){
        $pk = self::getSoundPacket($name, $pitch, $volume, $player->getPosition());
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public static function broadcastSound(Entity|Position $pos, string $name, float $pitch = 1.0, float $volume = 1.0){
        $vec3 = ($pos instanceof Entity ? $pos->getPosition() : $pos);
        $pk = self::getSoundPacket($name, $pitch, $volume, $vec3);
        $pos->getWorld()->broadcastPacketToViewers($vec3, $pk);
    }

    public static function getSoundPacket(string $name, float $pitch = 1.0, float $volume = 1.0, Vector3 $pos){
        $pk = new PlaySoundPacket();

        $pk->x = $pos->x;
        $pk->y = $pos->y;
        $pk->z = $pos->z;

        $pk->pitch = $pitch;
        $pk->volume = $volume;
        $pk->soundName = $name;

        return $pk;
    }
}