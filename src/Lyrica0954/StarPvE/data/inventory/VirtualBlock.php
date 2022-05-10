<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;

class VirtualBlock {

	public static function getTileCompoundTag(Vector3 $pos, string $id, string $name = ""): CompoundTag {
		$pos = $pos->floor();
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
			$pairPos = $pairPos->floor();
			$tileTag
				->setInt(Chest::TAG_PAIRX, $pairPos->x)
				->setInt(Chest::TAG_PAIRZ, $pairPos->z);
		}

		return $tileTag;
	}

	public static function getTileActorPacket(CompoundTag $tileTag): BlockActorDataPacket {
		return BlockActorDataPacket::create(
			new BlockPosition(
				$tileTag->getInt(Tile::TAG_X),
				$tileTag->getInt(Tile::TAG_Y),
				$tileTag->getInt(Tile::TAG_Z)
			),
			new CacheableNbt($tileTag)
		);
	}

	public static function getUpdateBlockPacket(Vector3 $pos, Block $block): UpdateBlockPacket {
		$pos = $pos->floor();
		return UpdateBlockPacket::create(
			new BlockPosition(
				$pos->x,
				$pos->y,
				$pos->z
			),
			RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId()),
			0,
			0
		);
	}

	/**
	 * @param Vector3 $pos
	 * @param Vector3|null $pairPos
	 * @param string $title
	 * 
	 * @return ClientboundPacket[]
	 */
	public static function createVirtualChest(Vector3 $pos, ?Vector3 $pairPos, string $title): array {
		$tag = self::getTileCompoundTag($pos, TileFactory::getInstance()->getSaveId(Chest::class), $title);
		$tag = self::addChestTag($tag, $pairPos);

		$packets = [
			self::getUpdateBlockPacket($pos, VanillaBlocks::CHEST()),
		];

		if ($pairPos !== null) {
			$packets[] = self::getUpdateBlockPacket($pairPos, VanillaBlocks::CHEST());
		}

		$packets[] = self::getTileActorPacket($tag);
		return $packets;
	}

	/**
	 * @param Player $player
	 * @param Vector3 $pos
	 * @param string $title
	 * @param bool $isLarge
	 * 
	 * @return Vector3[]
	 */
	public static function sendChest(Player $player, Vector3 $pos, string $title, bool $isLarge = false): array {
		$pairPos = ($isLarge ? ($pos->add(1, 0, 0)) : null);
		$packets = self::createVirtualChest($pos, $pairPos, $title);
		foreach ($packets as $packet) {
			$player->getNetworkSession()->sendDataPacket($packet, true);
		}
		$positions = [$pos];
		if ($pairPos !== null) {
			$positions[] = $pairPos;
		}

		return $positions;
	}
}
