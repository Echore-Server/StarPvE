<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\fightstyle\MeleeStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\RangedStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\Hostile;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\StarPvE\game\monster\fightstyle\MagicRangedStyle;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MagePiglin extends FightingEntity implements Hostile, ProjectileSource {
	use HealthBarEntity;

	protected int $healTick = 0;

	public static function getNetworkTypeId(): string {
		return EntityIds::PIGLIN;
	}

	protected float $reach = 10.0;

	public function getFollowRange(): float {
		return 50;
	}

	public function getName(): string {
		return "MagePiglin";
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(1.8, 0.6);
	}

	protected function getInitialFightStyle(): Style {
		return new MagicRangedStyle($this);
	}

	public function getAddtionalAttackCooldown(): int {
		return 40;
	}

	protected function onTick(int $currentTick, int $tickDiff = 1): void {
		$this->healTick++;
		if ($this->healTick >= 20) {
			$this->healTick = 0;
			foreach (EntityUtil::getWithinRange($this->getPosition(), 5) as $entity) {
				if ($entity instanceof Attacker) {
					$pos = $entity->getEyePos()->add(0, 0.5, 0);
					$source = new EntityRegainHealthEvent($entity, 4, EntityRegainHealthEvent::CAUSE_CUSTOM);
					$entity->heal($source);

					ParticleUtil::send(
						new SingleParticle,
						$this->getWorld()->getPlayers(),
						Position::fromObject($pos, $this->getWorld()),
						ParticleOption::spawnPacket("minecraft:heart_particle")
					);
				}
			}
		}
	}

	public function hitEntity(Entity $entity, float $range): void {
	}
}
