<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;

trait HealthBarEntity {

    protected int $barPercentage = 18;

    protected string $hpColor = "§a";
    protected string $nhpColor = "§c";

    protected string $format = "§c%s §7[%s§7]";

    private function barUpdate(?float $health = null, ?float $maxHealth = null) {
        $health = ($health === null ? $this->getHealth() : $health);
        $maxHealth = ($maxHealth === null ? $this->getMaxHealth() : $maxHealth);
        $this->setNameTag($this->getBarText($health, $maxHealth));
    }

    private function getBarText(float $health, float $maxHealth) {
        $perc = (int) round((($health / $maxHealth) * $this->barPercentage));

        $text = substr_replace(str_repeat("|", $this->barPercentage), $this->nhpColor, $perc, 0);

        $text = $this->hpColor . $text;

        return sprintf($this->format, round($this->getHealth(), 1), $text);
    }

    public function setHealth(float $amount): void {
        parent::setHealth($amount);

        $this->barUpdate();
    }

    public function setMaxHealth(int $amount): void {
        parent::setMaxHealth($amount);

        $this->barUpdate();
    }

    public function __construct(Location $location, ?CompoundTag $nbt = null) {
        parent::__construct($location, $nbt);

        if ($this instanceof Living) {
            $this->setNameTagAlwaysVisible(true);
            $this->setNameTagVisible(true);
            $this->barUpdate();
        }
    }
}
