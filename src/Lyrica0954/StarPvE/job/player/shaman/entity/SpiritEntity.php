<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman\entity;

use Lyrica0954\SmartEntity\entity\fightstyle\helper\HelpEntity;
use Lyrica0954\SmartEntity\entity\walking\Wolf;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\BuffUtil;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\item\Bread;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class SpiritEntity extends Wolf {
	use HelpEntity, HealthBarEntity;

	protected float $originalHealth;

	protected float $healthMultiplier = 1.0;

	protected float $emeraldGiven = 0;

	protected float $reach = 2.0;

	protected bool $assault = false;

	protected float $assaultArea = 0.0;

	protected ?Vector3 $lastPosition = null;

	public function getName(): string {
		return "Spirit";
	}

	public function getAddtionalAttackCooldown(): int {
		return 6;
	}

	public function hitEntity(Entity $entity, float $range): void {
		parent::hitEntity($entity, $range);

		if ($entity instanceof Living) {
			EntityUtil::slowdown($entity, 6, 0.9, SlowdownRunIds::get($this::class, 0));
		}
	}

	public function onInteract(Player $player, Vector3 $clickPos): bool {
		$item = $player->getInventory()->getItemInHand();
		if ($item instanceof Bread && !$this->getEffects()->has(VanillaEffects::REGENERATION())) {
			$heal = new EntityRegainHealthEvent($this, 8, EntityRegainHealthEvent::CAUSE_MAGIC);
			$this->heal($heal);
			$effect = new EffectInstance(VanillaEffects::REGENERATION(), 18 * 20, 3, true);
			$this->getEffects()->add($effect);
			$item->pop();
			PlayerUtil::broadcastSound($this, "random.eat");

			$player->getInventory()->setItemInHand($item);

			$this->healthMultiplier += 0.75;
			$baseHealth = $this->getOwningEntity() instanceof Entity ? $this->getOwningEntity()->getMaxHealth() : $this->originalHealth;
			$this->setMaxHealth((int) ($baseHealth * $this->healthMultiplier));

			$this->setScale(1.0 * (1.0 + $this->healthMultiplier / 12));
		}

		if ($item->getId() === VanillaItems::EMERALD()->getId() && $this->emeraldGiven < 60) {
			$this->emeraldGiven++;
			if ($this->emeraldGiven === 30) {
				BuffUtil::add($this, BuffUtil::BUFF_DMG_REDUCTION_PERC, 0.4);
				PlayerUtil::broadcastSound($this, "armor.equip_netherite", 0.2, 1.0);
				PlayerUtil::broadcastSound($this, "beacon.activate", 0.6, 1.0);
			} elseif ($this->emeraldGiven === 60) {
				BuffUtil::add($this, BuffUtil::BUFF_ATK_PERCENTAGE, 0.4);
				PlayerUtil::broadcastSound($this, "armor.equip_netherite", 0.2, 1.0);
				PlayerUtil::broadcastSound($this, "beacon.activate", 0.6, 1.0);
			} else {
				PlayerUtil::broadcastSound($this, "beacon.power", 4.0, 0.6);
			}

			$item->pop();

			$player->getInventory()->setItemInHand($item);
		}



		$ownerName = $this->getOwningEntity()?->getNameTag() ?? "None";
		$powerStr = "§a{$this->emeraldGiven}";
		if ($this->emeraldGiven >= 30) {
			$powerStr .= " §d-40% IncDmg";
			if ($this->emeraldGiven >= 60) {
				$powerStr .= ", §d+40% OutDmg";
			} else {
				$powerStr .= " §f/ §760 Eme";
			}
		} else {
			$powerStr .= " §f/ §730 Eme";
		}
		$tag = "{$powerStr}";
		$this->setNameTag($tag);

		return true;
	}

	public function attack(EntityDamageEvent $source): void {
		if ($source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Player) {
			$source->cancel();
		}

		parent::attack($source);
	}

	public function checkTarget(Entity $entity, float $range): bool {
		if ($this->friend) {
			return false;
		}

		return
			MonsterData::isMonster($entity) &&
			$entity instanceof Living &&
			$this->isInFollowRange($entity) &&
			!$entity instanceof Player &&
			$this->getName() !== $entity->getName() &&
			$entity->isAlive();
	}

	public function checkCurrentTarget() {
		return parent::checkCurrentTarget() && $this->target !== $this->helping && !$this->target instanceof Player;
	}

	public function assault(float $area): void {
		$this->assaultArea = $area;
		$this->assault = true;
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		$this->originalHealth = $this->getMaxHealth();
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$this->findTick = 100000000;
		$updated = parent::entityBaseTick($tickDiff);

		if ($this->isOnGround()) {
			$this->assault = false;
			$this->lastPosition = null;
		}

		if ($this->assault) {
			$pos = $this->getPosition();

			if ($this->lastPosition !== null) {
				foreach ($this->getWorld()->getEntities() as $entity) {
					if (MonsterData::isMonster($entity) && $entity->isAlive() && !$entity->isClosed() && !$entity instanceof SpiritEntity) {
						if (
							$entity->getBoundingBox()->expandedCopy(
								$this->assaultArea / 2,
								$this->assaultArea / 2,
								$this->assaultArea / 2
							)
							->calculateIntercept($pos, $this->lastPosition)
						) {
							$source = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getAttackDamage() * 5, [], 0.0);
							$entity->attack($source);
							$entity->setMotion($this->getMotion()->multiply(1.2));
						}
					}
				}
			}

			$this->lastPosition = $this->getPosition();
		}

		return $updated;
	}
}
