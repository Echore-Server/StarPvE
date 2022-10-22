<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\identity\ability\AbilitySignalIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\job\player\engineer\entity\GrenadeEntity;
use Lyrica0954\StarPvE\job\player\engineer\entity\ShieldBall;
use Lyrica0954\StarPvE\job\player\engineer\entity\ToxicBin;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\World;

class Engineer extends PlayerJob {

	protected function getInitialIdentityGroup(): IdentityGroup {
		return new IdentityGroup();
	}

	protected function getInitialAbility(): Ability {
		return new EMPAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new ThrowToxicBinSkill($this);
	}

	public function getName(): string {
		return "Engineer";
	}

	public function getDescription(): string {
		return
			"§7- §l§a支援[⚔]§r

敵に状態異常をかけることができる職業。
戦闘の支援や、進行の妨害が得意。";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}

	protected function init(): void {
		$this->defaultSpells = [
			new GrenadeSpell($this),
			(new IdentitySpell($this, "衝撃波"))
				->addIdentity(new AbilitySignalIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_ABILITY,
					EMPAbility::SIGNAL_SHOCKWAVE,
					"EMPが敵をノックバック/移動速度低下させるようになる"
				))
		];
	}

	public function __construct(?Player $player) {
		parent::__construct($player);

		$f = EntityFactory::getInstance();
		$f->register(GravityBall::class, function (World $world, CompoundTag $nbt): GravityBall {
			$itemTag = $nbt->getCompoundTag("Item");
			if ($itemTag === null) {
				throw new SavedDataLoadingException("Expected \"Item\" NBT tag not found");
			}

			$item = Item::nbtDeserialize($itemTag);
			if ($item->isNull()) {
				throw new SavedDataLoadingException("Item is invalid");
			}
			$entity = new GravityBall(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
			$entity->close();
			return $entity;
		}, ['starpve:gravity_ball'], EntityLegacyIds::ITEM);

		$f->register(ShieldBall::class, function (World $world, CompoundTag $nbt): ShieldBall {
			$itemTag = $nbt->getCompoundTag("Item");
			if ($itemTag === null) {
				throw new SavedDataLoadingException("Expected \"Item\" NBT tag not found");
			}

			$item = Item::nbtDeserialize($itemTag);
			if ($item->isNull()) {
				throw new SavedDataLoadingException("Item is invalid");
			}
			$entity = new ShieldBall(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
			$entity->close();
			return $entity;
		}, ['starpve:shield_ball'], EntityLegacyIds::ITEM);

		$f->register(ToxicBin::class, function (World $world, CompoundTag $nbt): ToxicBin {
			$entity = new ToxicBin(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
			$entity->close();
			return $entity;
		}, ['starpve:toxic_bin'], EntityLegacyIds::SPLASH_POTION);
	}
}
