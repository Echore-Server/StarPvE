<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

use Lyrica0954\StarPvE\utils\HealthBarEntity;
use pocketmine\entity\Attribute;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Villager extends Living {
    use HealthBarEntity;

    public function getName(): string{
        return "Villager";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6);
    }

    public static function getNetworkTypeId(): string{
        return EntityIds::VILLAGER;
    }

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);

        $this->barPercentage = 30;

        $this->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)->setValue(1.0);
    }
}