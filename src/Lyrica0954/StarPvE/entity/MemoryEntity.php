<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class MemoryEntity extends Entity implements Ghost{

    /**
     * @var \Closure[]
     */
    private array $tickHook = [];

    public static function getNetworkTypeId(): string{
        return EntityIds::SNOWBALL;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(0.00001, 0.00001);
    }

    public function __construct(Location $location, ?CompoundTag $nbt = null, float $gravity = 0.08, float $drag = 0.02){
        parent::__construct($location, $nbt);

        $this->gravity = $gravity;
        $this->drag = $drag;
    }

    public function addTickHook(\Closure $hook){
        $this->tickHook[] = $hook;
    }

    public function onUpdate(int $currentTick): bool{
        $hasUpdate = parent::onUpdate($currentTick);

        foreach($this->tickHook as $hook){
            ($hook)();
        }

        return $hasUpdate;
    }

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);

        $this->setInvisible(true);
    }
}