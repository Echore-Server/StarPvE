<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster\boss;

use Lyrica0954\MagicParticle\effect\PartDelayedEffect;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\walking\Skeleton;
use Lyrica0954\SmartEntity\utils\ProjectileHelper;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\event\PlayerDeathOnGameEvent;
use Lyrica0954\StarPvE\game\monster\fightstyle\NoneStyle;
use Lyrica0954\StarPvE\game\monster\MonsterMode;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

class Stray extends Skeleton implements Listener {
	use HealthBarEntity;

	protected int $beamTick = 0;
	public float $beamPeriod = 30;

	/**
	 * @var MemoryEntity[]
	 */
	protected array $sparks;
	protected bool $sparkMode;
	protected int $damageTick = 0;
	protected float $damagePeriod = 20;

	protected bool $finalAttack = false;
	protected int $finalAttackTick = 0;

	public function getFollowRange(): float {
		return 50;
	}

	public static function getNetworkTypeId(): string {
		return EntityIds::STRAY;
	}

	protected function onDispose(): void {
		parent::onDispose();

		foreach ($this->sparks as $spark) {
			$spark->close();
		}
		$this->sparks = [];

		HandlerListManager::global()->unregisterAll($this);
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		$this->setScale(1.5);
		$this->sparks = [];
		$this->sparkMode = false;
		Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}


	public function fireSpark(Position $pos, float $speed, float $yaw, float $pitch): MemoryEntity {
		$loc = Location::fromObject($pos, null);
		$spark = new MemoryEntity($loc, null, 0, 0);
		$spark->particleName = "minecraft:basic_crit_particle";
		$spark->triggerTarget = 0;
		$spark->chain = 0;
		$spark->speed = $speed;
		$this->sparks[spl_object_hash($spark)] = $spark;

		$motion = VectorUtil::getDirectionVector($yaw, $pitch);
		$spark->setMotion($motion->multiply($speed));
		$spark->addCloseHook(function (MemoryEntity $entity) {
			unset($this->sparks[spl_object_hash($entity)]);
		});
		$spark->addTickHook(function (MemoryEntity $entity) use ($speed) {
			if ($entity->isClosed()) {
				return;
			}
			if ($entity->getAge() >= 90) {
				$tar = $this->getTarget();
				if ($tar instanceof Entity && $entity->triggerTarget <= 0) {
					$entity->triggerTarget++;
					$angle = VectorUtil::getAngle($entity->getPosition(), $tar->getPosition()->add(0, 0.6, 0));
					$dir = VectorUtil::getDirectionVector($angle->x, $angle->y);
					$entity->setMotion($dir->multiply($speed));
					$entity->particleName = "starpve:soft_red_gas";
				}
			}

			if ($entity->getAge() >= 160) {
				$tar = $this->getTarget();
				if ($tar instanceof Entity && $entity->triggerTarget == 1) {
					$angle = VectorUtil::getAngle($entity->getPosition(), $tar->getPosition()->add(0, 0.6, 0));
					$dir = VectorUtil::getDirectionVector($angle->x, $angle->y);
					$entity->speed += 0.001;
					$entity->setMotion($dir->multiply($entity->speed));
					$entity->particleName = "starpve:soft_green_gas";
				}
			}

			if ($entity->getAge() >= 200) {
				$entity->close();
			}
			foreach (EntityUtil::getPlayersInsideVector($entity->getPosition(), new Vector3(0.5, 0.5, 0.5)) as $player) {
				$source = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0.5);
				$source->setAttackCooldown(0);
				$player->attack($source);
			}

			if ($entity->getAge() % 2 === 0) {
				(new SingleParticle)->sendToPlayers($entity->getWorld()->getPlayers(), $entity->getPosition(), ParticleOption::spawnPacket($entity->particleName, ""));
			}
		});

		return $spark;
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$update = parent::entityBaseTick($tickDiff);

		if ($this->getHealth() <= $this->getMaxHealth() / 3) {
			$this->sparkMode = true;
			$this->setFightStyle(new NoneStyle($this));
		}
		if ($this->sparkMode) {
			$this->beamTick += $tickDiff;
			if ($this->beamTick >= $this->beamPeriod) {
				PlayerUtil::broadcastSound($this, "random.bow", 1.6, 1.0);
				$this->beamTick = 0;
				$yaw = RandomUtil::rand_float(0, 360);
				$pitch = RandomUtil::rand_float(1, 5);
				$this->fireSpark(VectorUtil::keepAdd($this->getPosition(), 0, $this->getEyeHeight(), 0), 0.2, $yaw, $pitch);
			}

			$this->damageTick += $tickDiff;
			if ($this->damageTick >= $this->damagePeriod) {
				$this->damageTick = 0;
				$this->beamPeriod = max(3, $this->beamPeriod - 0.5);
			}
		}

		return $update;
	}

	public function onEntityDamage(EntityDamageEvent $event) {
		if ($event->getEntity() === $this) {
			if ($this->sparkMode) {
				EntityUtil::multiplyFinalDamage($event, 0.25);
			}
		}
	}

	public function onEntityDamageByChild(EntityDamageByChildEntityEvent $event) {
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		$child = $event->getChild();
		if ($damager instanceof Player) {
			if ($entity === $this) {
				ProjectileHelper::shootArrow(
					VectorUtil::keepAdd($this->getPosition(), 0, $this->getEyeHeight(), 0),
					$this,
					$damager->getPosition()->add(0, $damager->getEyeHeight(), 0),
					4,
					$this->getAttackDamage()
				);
			}
		}
	}

	public function onPlayerDeath(PlayerDeathOnGameEvent $event) {
		$player = $event->getPlayer();
		if ($player->getWorld() === $this->getWorld()) {
			EntityUtil::setHealthSynchronously($this, $this->getHealth() + 20);
		}
	}
}
