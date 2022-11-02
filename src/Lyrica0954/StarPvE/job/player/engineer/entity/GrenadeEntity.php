<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer\entity;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class GrenadeEntity extends Throwable {

	public float $range = 0.0;
	public float $areaDamage = 0.0;

	public static function getNetworkTypeId(): string {
		return EntityIds::SMALL_FIREBALL;
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {
	}

	protected function onHit(ProjectileHitEvent $event): void {

		foreach (EntityUtil::getWithinRange($this->getPosition(), $this->range, $this) as $entity) {
			if (MonsterData::isMonster($entity)) {
				$source = new EntityDamageByEntityEvent($this->getOwningEntity() ?? $this, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $this->areaDamage, [], 0.0);
				$source->setAttackCooldown(0);
				$entity->attack($source);

				EntityUtil::immobile($entity, 3 * 20);
			}
		}

		$molang = [];
		$molang[] = MolangUtil::variable("size", $this->range);

		ParticleUtil::send(
			new SingleParticle,
			$this->getWorld()->getPlayers(),
			$this->getPosition(),
			ParticleOption::spawnPacket("starpve:red_explosion", MolangUtil::encode($molang))
		);

		$this->flagForDespawn();
	}
}
