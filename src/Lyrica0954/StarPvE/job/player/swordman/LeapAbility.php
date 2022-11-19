<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Closure;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\Position;

class LeapAbility extends Ability implements Listener {
	use TickingController;

	const SIGNAL_PENETRATE = 0;

	public function getName(): string {
		return "ダッシュ";
	}

	public function getDescription(): String {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$duration = DescriptionTranslator::second($this->duration);
		$percentage = DescriptionTranslator::percentage($this->percentage, false, -1.0, true);
		return
			sprintf('§b発動時:§f 視線の先に突進する。無敵(例外あり)になり、
%1$s の敵に、%2$s ダメージとノックバック(%4$s) を与えて %3$s 秒動けなくさせる。
一度ダメージを与えるとキャンセルされる。
また、空中の敵に当てると、クールタイムが残り §c0.3秒 §fになる。', $area, $damage, $duration, $percentage);
	}

	protected function init(): void {
		$this->damage = new AbilityStatus(2.8);
		$this->area = new AbilityStatus(3.5);
		$this->duration = new AbilityStatus(1.55 * 20);
		$this->cooltime = new AbilityStatus(5 * 20 + 10);
		$this->percentage = new AbilityStatus(1.0);
		$this->speed = new AbilityStatus(1.35);
	}

	protected function onActivate(): ActionResult {
		$motion = VectorUtil::getDirectionHorizontal($this->player->getLocation()->yaw)->multiply($this->speed->get());
		$this->player->setMotion($motion);

		$std = new \stdClass;
		$std->tick = 0;
		$std->start = $this->player->getPosition();
		$std->last = $this->player->getPosition();

		$this->active = true;
		PlayerUtil::broadcastSound($this->player, "item.trident.riptide_1", 1.71, 0.7);

		TaskUtil::repeatingClosureFailure(function (Closure $fail) use ($motion, $std): void {
			$std->tick++;
			$currentMotion = $motion->multiply((1.0 - $std->tick / 20));
			$this->player->setMotion($currentMotion);

			if ($std->tick > 20) {
				$this->active = false;
				$fail();
			}


			$start = $this->player->getPosition()->add(0, 1.5, 0);
			$end = $start->addVector($currentMotion);

			$exp = $this->area->get() / 2;
			foreach ($this->player->getWorld()->getEntities() as $entity) {
				if (!MonsterData::isMonster($entity)) {
					continue;
				}

				$result = $entity->getBoundingBox()->expandedCopy($exp, $exp, $exp)->calculateIntercept($start, $end);
				if ($result instanceof RayTraceResult) {
					$success = false;
					foreach (EntityUtil::getWithinRange(Position::fromObject($result->getHitVector(), $entity->getWorld()), $this->area->get(), $this->player) as $target) {
						if (MonsterData::isMonster($target)) {
							$source = new EntityDamageByEntityEvent($this->player, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->damage->get());
							$source->setAttackCooldown(0);
							$perc = $this->percentage->get();
							$target->attack($source);
							EntityUtil::immobile($target, (int) $this->duration->get());
							if (!$source->isCancelled()) {
								$success = true;
								$velocity = EntityUtil::modifyKnockback($target, $std->start, 0.85 * $perc, 1.4 * $perc * ($target->isOnGround() ? 1.15 : 0.8));

								EntityUtil::setMotion($target, $velocity);
							}
						}
					}

					if ($success) {
						PlayerUtil::broadcastSound($this->player, "item.trident.return", 2.3, 0.5);
						if (!$this->signal->has(self::SIGNAL_PENETRATE)) {
							$this->player->setMotion(new Vector3(0, 0, 0));
							$this->active = false;
							$fail();

							if (!$entity->isOnGround()) {
								$this->cooltimeHandler->setRemain($this->cooltimeHandler->calculate(6));
							}
						}
						break;
					}
				}
			}

			$std->last = $this->player->getPosition();
		}, 1);

		return ActionResult::SUCCEEDED();
	}

	public function onDamage(EntityDamageByEntityEvent $event) {
		$entity = $event->getEntity();
		if ($entity === $this->player) {
			if ($this->active) {
				$event->cancel();
			}
		}
	}
}
