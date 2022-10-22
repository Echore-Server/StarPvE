<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\StarPvE\entity\EntityStateManager;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\entity\state\ElectrificationState;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\game\wave\MonsterFactory;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ability\ThrowEntityAbilityBase;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

/**
 * todo: 無重力のやつを投擲して1秒後にこれが発動するようにする
 */
class EMPAbility extends Ability {

	public const SIGNAL_SHOCKWAVE = 0;

	public function getName(): string {
		return "EMP";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$percentage = DescriptionTranslator::percentage($this->percentage);
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf(
				mb_convert_encoding('
§b発動時:§f エネルギー弾を投擲して、 §c2.5秒§f 後に %1$s 以内の敵に対して §b効果§f を発動させる

§b効果§f: 体力が %3$s§f 以内の敵に即死ダメージを与える。
§b効果§f: %4$s §d帯電 §f状態にする。
§b効果§f: §dクリーパー§f の場合、 %2$s ダメージを与える。
', "UTF-8", "UTF-8"),
				$area,
				$damage,
				$percentage,
				$duration,
			);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(8.0);
		$this->damage = new AbilityStatus(20.0);
		$this->percentage = new AbilityStatus(0.1);
		$this->duration = new AbilityStatus(6 * 20);
		$this->cooltime = new AbilityStatus(14 * 20);
	}

	protected function onActivate(): ActionResult {

		$e = new MemoryEntity(Location::fromObject($this->player->getEyePos(), $this->player->getWorld()), null, 0.0, 0.0);
		$e->setMotion($this->player->getDirectionVector()->multiply(0.3));

		$run = function (Position $pos) {
			$par = new SphereParticle($this->area->get(), 12, 12, 360, -90, 0);
			ParticleUtil::send($par, $this->player->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("starpve:soft_green_gas", ""));

			PlayerUtil::broadcastSound($pos, "mob.warden.sonic_boom", 1.5, 0.6);

			$entities = EntityUtil::getWithinRange($pos, $this->area->get());

			foreach ($entities as $entity) {
				if (MonsterData::isMonster($entity)) {

					if ($entity instanceof Creeper) {
						$source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->damage->get(), [], 0);
						$source->setAttackCooldown(0);
						$entity->attack($source);
					}

					if ($entity instanceof LivingBase) {
						if ($entity->getHealth() <= ($entity->getMaxHealth() * $this->percentage->get())) {
							$source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageEvent::CAUSE_MAGIC, $entity->getMaxHealth(), [], 0);
							$source->setAttackCooldown(0);
							$entity->attack($source);
						}

						$id = EntityStateManager::nextStateId();

						EntityStateManager::start(new ElectrificationState($entity, 1, $this->area->get()), $id);

						TaskUtil::delayed(new ClosureTask(function () use ($entity, $id) {
							EntityStateManager::end($entity->getId(), $id);
						}), (int) $this->duration->get());

						if ($this->signal->has(self::SIGNAL_SHOCKWAVE)) {
							if (!MonsterData::equal($entity, DefaultMonsters::ATTACKER)) {
								$motion = EntityUtil::modifyKnockback($entity, $pos, 1.5, 0.0);
								TaskUtil::repeatingClosureLimit(function () use ($entity, $motion) {
									$motion = $motion->multiply(0.5);
									$entity->addMotion($motion->x, $motion->y, $motion->z);
								}, 1, 6);
								EntityUtil::slowdown($entity, 4 * 20, 0.2, SlowdownRunIds::get($this::class));
							}
						}
					}
				}
			}
		};

		$e->addTickHook(function (MemoryEntity $e) use ($run): void {
			ParticleUtil::send(
				new SingleParticle,
				$e->getWorld()->getPlayers(),
				$e->getPosition(),
				ParticleOption::spawnPacket("starpve:soft_green_gas", "")
			);

			if ($e->getAge() > 50) {
				$run($e->getPosition());
				$e->flagForDespawn();
			}
		});

		return ActionResult::SUCCEEDED();
	}
}
