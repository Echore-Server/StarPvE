<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer\entity;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\effect\PartDelayedEffect;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use Ramsey\Uuid\Type\Integer;

class FreezeArrow extends SpecialArrow implements Listener {

	/**
	 * @var EffectGroup|null
	 */
	public ?EffectGroup $playerEffects = null;

	/**
	 * @var EffectGroup|null
	 */
	public ?EffectGroup $explodeEffects = null;

	protected int $explodeTick = 0;

	protected float $particleCTick = 0;
	protected float $areaC = 0;

	protected int $soundTick = 0;
	protected int $soundPeriod = 20;

	private bool $activatedInternal = false;

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
		parent::onHitBlock($blockHit, $hitResult);
		$pos = $hitResult->getHitVector();
		$pos->y = $blockHit->getPosition()->getY();
		$this->activatedPosition = $pos;
		$this->activated = false;

		$this->areaC = $this->area;
		$this->activatedInternal = true;
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$update = parent::entityBaseTick($tickDiff);
		$players = $this->getWorld()->getPlayers();
		(new SingleParticle)->sendToPlayers($players, $this->getPosition(), ParticleOption::spawnPacket("minecraft:ice_evaporation_emitter", ""));

		if ($this->activatedInternal) {
			$vec = $this->activatePosition;
			if ($vec instanceof Vector3) {
				$this->activeTick += $tickDiff;
				$this->age += $tickDiff;
				$this->particleTick += $tickDiff;
				$this->explodeTick += $tickDiff;
				$this->particleCTick += $tickDiff;
				$this->soundTick += $tickDiff;

				$pos = VectorUtil::insertWorld($vec, $this->getWorld());
				$players = $this->getWorld()->getPlayers();

				if ($this->activeTick >= $this->period) {
					$this->activeTick = 0;
					$ecount = 0;
					$entities = EntityUtil::getWithinRange($pos, $this->areaC);
					$this->areaC = $this->area;

					foreach ($entities as $entity) {
						if (MonsterData::isMonster($entity) && $entity instanceof Living) {
							$ecount += 1;
							if ($ecount <= 14) {
								$this->areaC += (1 * 0.5);
							}
							$effects = ($this->areaEffects ?? (new EffectGroup()));
							$effects->apply($entity);
							if ($entity instanceof FightingEntity && !MonsterData::equal($entity, DefaultMonsters::ATTACKER)) {
								if (!$entity->isFriend()) {
									$entity->setFriend(true);
									$beforeTarget = $entity->getTarget();
									TaskUtil::reapeatingClosureCheck(function () use ($entity) {
										$min = EntityUtil::getCollisionMin($entity);
										$emitter = EmitterParticle::createEmitterForEntity($entity, 0.3, 3);
										$emitter->sendToPlayers($entity->getWorld()->getPlayers(), VectorUtil::insertWorld($min, $entity->getWorld()), ParticleOption::spawnPacket("minecraft:magnesium_salts_emitter", ""));
									}, 6, function () use ($entity) {
										return ($entity->isAlive() && !$entity->isClosed() && $entity->isFriend());
									});

									TaskUtil::delayed(new ClosureTask(function () use ($entity, $beforeTarget) {
										$entity->setFriend(false);
										$entity->setTarget($beforeTarget);
									}), $this->duration);
								}
							}
						} elseif ($entity instanceof Player) {
							$effects = ($this->playerEffects ?? (new EffectGroup()));
							$effects->apply($entity);

							#$pk = CameraShakePacket::create(5.0, 1.0, CameraShakePacket::TYPE_POSITIONAL, CameraShakePacket::ACTION_ADD);
							#$entity->getNetworkSession()->sendDataPacket($pk);
						}
					}
				}

				if ($this->particleTick >= 20) {
					$this->particleTick = 0;

					$spar = (new SphereParticle($this->areaC, 6, 6, 360, -90, 0));
					$eff = new PartDelayedEffect(new SaturatedLineworkEffect($this->areaC, 3, 0.0, 7, 360, -90, 0), 3, 1, true);
					$spar->sendToPlayers($players, $pos, ParticleOption::spawnPacket("starpve:freeze_gas", ""));
					$eff->sendToPlayers($players, $pos, ParticleOption::spawnPacket("minecraft:magnesium_salts_emitter", ""));
				}

				if ($this->soundTick >= $this->soundPeriod) {
					$this->soundTick = 0;

					PlayerUtil::broadcastSound($this, "respawn_anchor.ambient", 1.5, 1.0);
					PlayerUtil::broadcastSound($this, "respawn_anchor.charge", 0.5, 1.0);

					$sec = 2;
					$p = ($this->duration - ($sec * 20));
					if ($this->explodeTick >= $p) {
						$diff = ($this->explodeTick - $p);
						# wont minus
						if ($diff >= 0) {
							$this->soundPeriod = (int) (20 - ($diff / $sec));
						}
					}
				}

				if ($this->explodeTick >= $this->duration) {
					$std = new \stdClass;
					$std->volume = 0.2;
					PlayerUtil::broadcastSound($this, "random.explode", 1.0, 0.6);
					TaskUtil::repeatingClosureLimit(function () use ($std) {
						$std->volume += 0.2;
						foreach ($this->getWorld()->getPlayers() as $player) {
							PlayerUtil::playSound($player, "respawn_anchor.set_spawn", 0.5, $std->volume);
						}
					}, 2, 4);
					(new SingleParticle)->sendToPlayers($this->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("starpve:smoke_explosion", ""));
					(new SingleParticle)->sendToPlayers($this->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("starpve:smoke_explosion", ""));
					(new SingleParticle)->sendToPlayers($this->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("starpve:smoke_explosion", ""));

					foreach (EntityUtil::getWithinRange($pos, PHP_INT_MAX) as $entity) {
						if (MonsterData::isMonster($entity)) {
							$effects = ($this->explodeEffects ?? (new EffectGroup()));
							$effects->apply($entity);
							if (!MonsterData::equal($entity, DefaultMonsters::ATTACKER)) {
								$motion = EntityUtil::modifyKnockback($entity, $this, 3.5, 1.0);
								$entity->setMotion($motion);
							}
						}
					}
					$this->kill();
				}
			}
		}

		return $update;
	}

	public function canCollideWith(Entity $entity): bool {
		return false;
	}
}
