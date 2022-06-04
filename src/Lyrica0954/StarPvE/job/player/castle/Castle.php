<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\castle;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthArgIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\castle\entity\TrapDevice;
use Lyrica0954\StarPvE\job\player\castle\entity\VoidDevice;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\World;

class Castle extends PlayerJob {

	public function __construct(?Player $player) {
		parent::__construct($player);

		$f = EntityFactory::getInstance();
		$f->register(TrapDevice::class, function (World $world, CompoundTag $nbt): TrapDevice {
			$itemTag = $nbt->getCompoundTag("Item");
			if ($itemTag === null) {
				throw new SavedDataLoadingException("Expected \"Item\" NBT tag not found");
			}

			$item = Item::nbtDeserialize($itemTag);
			if ($item->isNull()) {
				throw new SavedDataLoadingException("Item is invalid");
			}
			$entity = new TrapDevice(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
			$entity->close();
			return $entity;
		}, ['starpve:trap_device'], EntityLegacyIds::ITEM);

		$f->register(VoidDevice::class, function (World $world, CompoundTag $nbt): VoidDevice {
			$itemTag = $nbt->getCompoundTag("Item");
			if ($itemTag === null) {
				throw new SavedDataLoadingException("Expected \"Item\" NBT tag not found");
			}

			$item = Item::nbtDeserialize($itemTag);
			if ($item->isNull()) {
				throw new SavedDataLoadingException("Item is invalid");
			}
			$entity = new VoidDevice(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
			$entity->close();
			return $entity;
		}, ['starpve:void_device'], EntityLegacyIds::ITEM);
	}

	protected function getInitialAbility(): Ability {
		return new ThrowTrapAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new ThrowVoidSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		$g = new IdentityGroup();
		$list = [
			new AddMaxHealthArgIdentity(null, 10)
		];
		$g->addAll($list);
		return $g;
	}

	public function getName(): string {
		return "Castle";
	}

	public function getDescription(): string {
		return
			"§7- §l§9防衛§r

単体の敵に対して、高ダメージを出すことができる職業。
範囲攻撃は他の職業より弱いが、他の職業と組み合わせればもっとも強い職業にもなりうる。";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}
}
