<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class MemoryEntity extends Entity {

    /**
     * @var \Closure[]
     */
    private array $tickHook = [];

    /**
     * @var \Closure[]
     */
    private array $closeHook = [];

    protected int $age = 0;

    public static function getNetworkTypeId(): string {
        return EntityIds::SNOWBALL;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.00001, 0.00001);
    }

    public function __construct(Location $location, ?CompoundTag $nbt = null, float $gravity = 0.08, float $drag = 0.02) {
        parent::__construct($location, $nbt);

        $this->gravity = $gravity;
        $this->drag = $drag;
    }

    public function canBeCollidedWith(): bool {
        return false;
    }

    public function getAge(): int {
        return $this->age;
    }

    public function addTickHook(\Closure $hook) {
        $this->tickHook[] = $hook;
    }

    public function addCloseHook(\Closure $hook) {
        $this->closeHook[] = $hook;
    }

    protected function onDispose(): void {
        foreach ($this->closeHook as $hook) {
            ($hook)($this);
        }
        $this->closeHook = [];
        parent::onDispose();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $update = parent::entityBaseTick($tickDiff);
        $this->age += $tickDiff;
        return $update;
    }

    public function onUpdate(int $currentTick): bool {
        foreach ($this->tickHook as $hook) {
            ($hook)($this);
        }
        $hasUpdate = parent::onUpdate($currentTick);

        return $hasUpdate;
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $this->setInvisible(true);
    }
}
