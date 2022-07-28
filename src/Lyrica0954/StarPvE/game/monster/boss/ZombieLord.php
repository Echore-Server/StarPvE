<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster\boss;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\game\wave\SpawnAnimation;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Server;
use pocketmine\world\particle\Particle;

class ZombieLord extends SmartZombie implements Listener {
	use HealthBarEntity;

	protected float $reach = 2.25;

	protected int $lastParticle = 0;

	public float $defendArea = 6.0;

	protected int $callTick = 0;

	protected int $regenTick = 0;

	public function getFollowRange(): float {
		return 50;
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		$this->setScale(1.5);
		Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}

	protected function onDispose(): void {
		parent::onDispose();


		ParticleUtil::send((new SaturatedLineworkEffect(10, 3, 0.1, 10, 360, -90, 0)), $this->getWorld()->getPlayers(), $this->getPosition(), ParticleOption::spawnPacket("starpve:soft_red_gas", ""));

		HandlerListManager::global()->unregisterAll($this);
	}

	protected function callZombie() {
		$tick = 50;
		$std = new \stdClass;
		$std->y = 0;
		$std->step = 0;
		$std->tick = 0;
		$animation = new SpawnAnimation(function (Living $entity) {
			return false;
		}, 1);
		$animation->setInitiator(function (Living $entity) {
			if (($target = $this->getTarget()) instanceof Entity) {
				if ($entity instanceof FightingEntity) {
					$entity->setTarget($target);
				}

				$dist = $target->getPosition()->distance($this->getPosition());
				$motion = EntityUtil::modifyKnockback($target, $this, $dist / 3, 1.0);
				$entity->setMotion($motion);

				$entity->setMaxHealth(10);
			} else {
				$entity->setMotion(new Vector3(RandomUtil::rand_float(-1.0, 1.0), 0.4, RandomUtil::rand_float(-1.0, 1.0)));
			}
		});

		$monsters = new WaveMonsters(new MonsterData(DefaultMonsters::ZOMBIE, 1, $animation));

		$game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
		if ($game instanceof Game) {
			if (!$game->isClosed()) {
				$game->getWaveController()->spawnMonster($monsters, $this->getPosition());
			}
		}

		ParticleUtil::send(new SingleParticle, $this->getWorld()->getPlayers(), $this->getPosition(), ParticleOption::spawnPacket("minecraft:knockback_roar_particle", ""));
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$update = parent::entityBaseTick($tickDiff);


		$this->callTick += $tickDiff;
		if ($this->callTick >= 320 && !$this->isFriend()) {
			$this->callTick = 0;
			$this->callZombie();
		}

		if ($this->getHealth() <= ($this->getMaxHealth() / 3) && !$this->isFriend()) {
			$this->regenTick += $tickDiff;
			if ($this->regenTick >= 3) {
				$this->regenTick = 0;
				$heal = 0.5;
				$pos = $this->getPosition();
				$pos->y += 0.3;
				foreach (EntityUtil::getWithinRange($this->getPosition(), $this->defendArea) as $entity) {
					if (MonsterData::isMonster($entity)) {
						if ($entity !== $this) {
							$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_SUICIDE, $heal);
							$epos = $entity->getPosition();
							$epos->y += 0.3;
							$ev->setAttackCooldown(0);
							$entity->attack($ev);
							ParticleUtil::send(new LineParticle($pos, 1), $this->getWorld()->getPlayers(), $epos, ParticleOption::spawnPacket("starpve:red_gas", ""));

							PlayerUtil::broadcastSound($entity, "dig.nylium", 0.65, 0.8);

							$regain = new EntityRegainHealthEvent($this, $ev->getFinalDamage(), EntityRegainHealthEvent::CAUSE_CUSTOM);
							$this->heal($regain);
						}
					}
				}
			}
		}

		return $update;
	}

	public function onEntityDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();

		if (MonsterData::isMonster($entity)) {
			if ($event->getCause() !== EntityDamageEvent::CAUSE_CUSTOM && $event->getCause() !== EntityDamageEvent::CAUSE_SUICIDE) {
				if ($entity !== $this) {
					$pos = $this->getPosition();
					$pos->y += 0.3;
					$epos = $entity->getPosition();
					$epos->y += 0.3;

					if ($pos->distance($epos) <= $this->defendArea) {
						$players = $this->getWorld()->getPlayers();
						ParticleUtil::send(new LineParticle($pos, 2), $players, $epos, ParticleOption::spawnPacket("minecraft:falling_dust_top_snow_particle", ""));

						EntityUtil::multiplyFinalDamage($event, 0.65);

						PlayerUtil::broadcastSound($entity, "item.shield.block", 1.5, 0.3);
					}
				}
			}
		}
	}
}
