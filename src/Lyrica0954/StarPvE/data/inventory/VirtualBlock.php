<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use pocketmine\block\Block;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class VirtualBlock {

	public static function getTileCompoundTag(Vector3 $pos, string $id, string $name = ""): CompoundTag {
		$tag = CompoundTag::create()
			->setString(Tile::TAG_ID, $id)
			->setInt(Tile::TAG_X, $pos->x)
			->setInt(Tile::TAG_Y, $pos->y)
			->setInt(Tile::TAG_Z, $pos->z);

		if ($name != "") {
			$tag->setString(Nameable::TAG_CUSTOM_NAME, $name);
		}

		return $tag;
	}

	public static function addChestTag(CompoundTag $tileTag, ?Vector3 $pairPos): CompoundTag {
		if ($pairPos !== null) {
			$tileTag
				->setInt(Chest::TAG_PAIRX, $pairPos->x)
				->setInt(Chest::TAG_PAIRZ, $pairPos->z);
		}

		return $tileTag;
	}
}
