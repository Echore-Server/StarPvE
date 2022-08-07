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
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
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
use pocketmine\world\Position;
use Ramsey\Uuid\Type\Integer;

class FreezeArrow extends SpecialArrow implements Listener {

	protected int $explodeTick = 0;

	protected float $particleCTick = 0;

	protected int $soundTick = 0;
	protected int $soundPeriod = 20;

	protected float $originalArea = 0;

	private bool $activatedInternal = false;

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
		parent::onHitBlock($blockHit, $hitResult);
		$pos = $hitResult->getHitVector();
		$pos->y = $blockHit->getPosition()->getY();
		$this->activatedPosition = $pos;
		$this->activated = false;
		$this->activatedInternal = true;

		$this->originalArea = $this->area;

		$pos = Position::fromObject($pos, $this->getWorld());

		$entities = EntityUtil::getWithinRange($pos, $this->area);

		$par = (new SphereParticle($this->area, 12, 12, 360, -90, 0));
		ParticleUtil::send($par, $this->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("starpve:freeze_gas", ""));

		foreach ($entities as $entity) {
			if (MonsterData::isMonster($entity) && $entity instanceof Living) {
				$source = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, $this->damage);
				$entity->attack($source);
			}
		}



		#$this->setMotion(new Vector3(0, 0.01, 0));
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$update = parent::entityBaseTick($tickDiff);
		$players = $this->getWorld()->getPlayers();
		ParticleUtil::send(new SingleParticle, $players, $this->getPosition(), ParticleOption::spawnPacket("minecraft:ice_evaporation_emitter", ""));

		if ($this->activatedInternal) {
			$vec = $this->activatePosition;
			if ($vec instanceof Vector3) {
				$this->activeTick += $tickDiff;
				$this->age += $tickDiff;
				$this->particleTick += $tickDiff;
				$this->explodeTick += $tickDiff;
				$this->particleCTick += $tickDiff;
				$this->soundTick += $tickDiff;

				$per = ($this->originalArea - 2.5) / $this->duration;
				$this->area -= $per * $tickDiff;

				$pos = Position::fromObject($vec, $this->getWorld());
				$players = $this->getWorld()->getPlayers();

				if ($this->activeTick >= $this->period) {
					$this->activeTick = 0;

					$entities = EntityUtil::getWithin($pos, $this->area - 1.5, $this->area + 0.5);

					foreach ($entities as $entity) {
						if (MonsterData::isMonster($entity) && $entity instanceof Living) {
							$motion = EntityUtil::modifyKnockback($entity, $this, 1.0, 0.0);
							$motion = $motion->multiply(-0.9);
							$entity->setMotion($motion);
						}
					}
				}

				if ($this->particleTick >= 32) {
					$this->particleTick = 0;

					$spar = (new SphereParticle($this->area, 12, 12, 360, -90, 0));
					ParticleUtil::send($spar, $players, $pos, ParticleOption::spawnPacket("starpve:freeze_gas", ""));
				}

				if ($this->soundTick >= $this->soundPeriod) {
					$this->soundTick = 0;

					PlayerUtil::broadcastSound($this, "respawn_anchor.ambient", 1.5, 1.0);
					PlayerUtil::broadcastSound($this, "respawn_anchor.charge", 0.5, 1.0);

					$sec = 4;
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
