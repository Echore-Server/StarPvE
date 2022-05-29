<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\entity\Living;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class ArmorSet {

    protected Armor|ItemBlock $helmet; #itemblock = air
    protected Armor|ItemBlock $chestplate;
    protected Armor|ItemBlock $leggings;
    protected Armor|ItemBlock $boots;

    public static function leather() {
        $f = ItemFactory::getInstance();
        $h = $f->get(ItemIds::LEATHER_HELMET);
        $c = $f->get(ItemIds::LEATHER_CHESTPLATE);
        $l = $f->get(ItemIds::LEATHER_LEGGINGS);
        $b = $f->get(ItemIds::LEATHER_BOOTS);
        return new self($h, $c, $l, $b);
    }

    public static function iron() {
        $f = ItemFactory::getInstance();
        $h = $f->get(ItemIds::IRON_HELMET);
        $c = $f->get(ItemIds::IRON_CHESTPLATE);
        $l = $f->get(ItemIds::IRON_LEGGINGS);
        $b = $f->get(ItemIds::IRON_BOOTS);
        return new self($h, $c, $l, $b);
    }

    public static function chainmail() {
        $f = ItemFactory::getInstance();
        $h = $f->get(ItemIds::CHAINMAIL_HELMET);
        $c = $f->get(ItemIds::CHAINMAIL_CHESTPLATE);
        $l = $f->get(ItemIds::CHAINMAIL_LEGGINGS);
        $b = $f->get(ItemIds::CHAINMAIL_BOOTS);
        return new self($h, $c, $l, $b);
    }

    public static function gold() {
        $f = ItemFactory::getInstance();
        $h = $f->get(ItemIds::GOLDEN_HELMET);
        $c = $f->get(ItemIds::GOLDEN_CHESTPLATE);
        $l = $f->get(ItemIds::GOLDEN_LEGGINGS);
        $b = $f->get(ItemIds::GOLDEN_BOOTS);
        return new self($h, $c, $l, $b);
    }

    public static function diamond() {
        $f = ItemFactory::getInstance();
        $h = $f->get(ItemIds::DIAMOND_HELMET);
        $c = $f->get(ItemIds::DIAMOND_CHESTPLATE);
        $l = $f->get(ItemIds::DIAMOND_LEGGINGS);
        $b = $f->get(ItemIds::DIAMOND_BOOTS);
        return new self($h, $c, $l, $b);
    }

    public static function none() {
        return new self(null, null, null, null);
    }

    public function __construct(?Armor $helmet, ?Armor $chestplate, ?Armor $leggings, ?Armor $boots) {
        $this->helmet = $this->replaceAir($helmet);
        $this->chestplate = $this->replaceAir($chestplate);
        $this->leggings = $this->replaceAir($leggings);
        $this->boots = $this->replaceAir($boots);
    }

    public function replaceAir(?Armor $armor) {
        return ($armor instanceof Armor) ? $armor : (ItemFactory::getInstance()->get(ItemIds::AIR));
    }

    public function setUnbreakable(bool $unbreakable = true) {
        $parts = [
            $this->helmet,
            $this->chestplate,
            $this->leggings,
            $this->boots
        ];

        foreach ($parts as $part) {
            if ($part instanceof Durable) {
                $part->setUnbreakable($unbreakable);
            }
        }
    }

    public function getHelmet() {
        return $this->helmet;
    }

    public function getChestplate() {
        return $this->chestplate;
    }

    public function getLeggings() {
        return $this->leggings;
    }

    public function getBoots() {
        return $this->boots;
    }

    public function equip(Living $living) {
        $inv = $living->getArmorInventory();
        $inv->setHelmet($this->helmet);
        $inv->setChestplate($this->chestplate);
        $inv->setLeggings($this->leggings);
        $inv->setBoots($this->boots);
    }
}
