<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\StarPvE\entity\EntityStateManager;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\entity\state\ElectrificationState;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\wave\MonsterData;
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
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\ClosureTask;

class EMPAbility extends Ability {

	public function getName(): string {
		return "EMP";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$percentage = DescriptionTranslator::percentage($this->percentage);
		$duration = DescriptionTranslator::second($this->duration);
		$amount = DescriptionTranslator::number($this->amount, "体");
		return
			sprintf(
				mb_convert_encoding('
§b発動時:§f %1$s 以内の敵に対して §b効果§f を発動させる
§b発動時:§f %1$s 以内の特殊投擲物を消滅させる

§b効果§f: 体力が %3$s§f 以内の敵に即死ダメージを与える。
§b効果§f: %4$s §d帯電 §f状態にする。
§b効果§f: §dクリーパー§f の場合、 %2$s ダメージを与える。

§d帯電§f 状態の敵に攻撃すると、ほかの敵にもダメージを与えることができる。 (最大 %5$s)
', "UTF-8", "UTF-8"),
				$area,
				$damage,
				$percentage,
				$duration,
				$amount
			);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(8.0);
		$this->damage = new AbilityStatus(20.0);
		$this->percentage = new AbilityStatus(0.14);
		$this->duration = new AbilityStatus(10 * 20);
		$this->amount = new AbilityStatus(1);
		$this->cooltime = new AbilityStatus(10 * 20);
	}

	protected function onActivate(): ActionResult {

		$par = new SphereParticle($this->area->get(), 12, 12, 360, -90, 0);
		ParticleUtil::send($par, $this->player->getWorld()->getPlayers(), $this->player->getPosition(), ParticleOption::spawnPacket("starpve:soft_green_gas", ""));

		PlayerUtil::broadcastSound($this->player->getPosition(), "mob.warden.sonic_boom", 1.5, 0.6);

		$entities = EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get());

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

					EntityStateManager::start(new ElectrificationState($entity, (int) $this->amount->get(), $this->area->get()), $id);

					TaskUtil::delayed(new ClosureTask(function () use ($entity, $id) {
						EntityStateManager::end($entity->getId(), $id);
					}), (int) $this->duration->get());
				}
			} elseif ($entity instanceof MemoryEntity) {
				$entity->close();
			}
		}


		return ActionResult::SUCCEEDED();
	}
}
