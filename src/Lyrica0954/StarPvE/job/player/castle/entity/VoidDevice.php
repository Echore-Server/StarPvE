<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\castle\entity;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\entity\item\GhostItemEntity;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\EntityDataHelper;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;

class VoidDevice extends GhostItemEntity implements Listener {

	protected bool $active = false;

	protected int $tick = 0;
	protected int $visTick = 0;

	/**
	 * @var int[]
	 */
	protected array $attackTick;

	public float $damage = 0;

	public int $duration = 0;

	public float $percentage = 1.0;

	public float $area = 0;
	public float $damageArea = 0;

	protected int $lastDamageVisTick = 0;

	protected function onDispose(): void {
		HandlerListManager::global()->unregisterAll($this);
		parent::onDispose();
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		StarPvE::getInstance()->getServer()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if ($this->isOnGround() && !$this->active) {
			PlayerUtil::broadcastSound($this, "random.anvil_land", 1.5, 0.2);
			$this->active = true;
			$this->gravity = 0.0;
			$this->drag = 0.0;
			$this->motion = new Vector3(0, 0, 0);
			$this->teleport($this->getPosition()->add(0, 0.5, 0));
		}

		if ($this->active) {
			$this->tick += $tickDiff;
			$this->visTick += $tickDiff;

			if ($this->visTick >= 15) {
				$this->visTick = 0;
				(new CircleParticle($this->area, 6))->sendToPlayers($this->getWorld()->getPlayers(), $this->getPosition(), ParticleOption::spawnPacket("minecraft:falling_dust_concrete_powder_particle", ""));
			}

			if ($this->tick >= $this->duration) {
				$this->kill();
			}
		} else {
			(new SingleParticle)->sendToPlayers(
				$this->getWorld()->getPlayers(),
				VectorUtil::insertWorld(
					$this->getOffsetPosition(
						$this->getPosition()
					),
					$this->getWorld()
				),
				ParticleOption::spawnPacket("minecraft:balloon_gas_particle", "")
			);
		}



		return $hasUpdate;
	}

	/**
	 * @param EntityDamageEvent $event
	 * 
	 * @return void
	 * 
	 */
	public function onEntityDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();

		if (MonsterData::isMonster($entity)) {
			if ($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
				if ($this->getWorld() === $entity->getWorld()) {
					$dist = $this->getPosition()->distance($entity->getPosition());
					if ($dist <= $this->area) {
						$finalDamage = $event->getFinalDamage();
						if (Server::getInstance()->getTick() - $this->lastDamageVisTick >= 10) {
							$this->lastDamageVisTick = Server::getInstance()->getTick();

							(new CircleParticle($this->damageArea, 6))->sendToPlayers($this->getWorld()->getPlayers(), $this->getPosition(), ParticleOption::spawnPacket("minecraft:falling_dust_top_snow_particle", ""));
						}
						foreach (EntityUtil::getWithinRange($this->getPosition(), $this->damageArea) as $hent) {
							if (MonsterData::isMonster($hent)) {
								$damage = $finalDamage / 2;

								$source = new EntityDamageEvent($hent, EntityDamageEvent::CAUSE_MAGIC, $damage);
								$hent->attack($source);
							}
						}
						EntityUtil::multiplyFinalDamage($event, $this->percentage);
					}
				}
			}
		}
	}
}
