<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\item;

use Locale;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class MonsterDropItem extends ItemEntity {

    private string $soundName = "";
    private float $soundPitch = 1.0;
    private float $soundVolume = 1.0;

    public function setSound(string $soundName, float $soundPitch, float $soundVolume){
        $this->soundName = $soundName;
        $this->soundPitch = $soundPitch;
        $this->soundVolume = $soundVolume;
    }

    public function onCollideWithPlayer(Player $player): void{
        
        if ($this->pickupDelay > 0){
            return;
        }
        
        if ($this->getOwningEntity() !== null && $this->getOwningEntity() !== $player){
            return;
        }

        PlayerUtil::playSound($player, $this->soundName, $this->soundPitch, $this->soundVolume);

        parent::onCollideWithPlayer($player);
    }
}