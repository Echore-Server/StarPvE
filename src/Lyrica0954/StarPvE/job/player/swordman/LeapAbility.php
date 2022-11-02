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
一度ダメージを与えるとキャンセルされる。', $area, $damage, $duration, $percentage);
	}

	protected function init(): void {
		$this->damage = new AbilityStatus(3.5);
		$this->area = new AbilityStatus(3.5);
		$this->duration = new AbilityStatus(1.35 * 20);
		$this->cooltime = new AbilityStatus(5 * 20 + 10);
		$this->percentage = new AbilityStatus(1.0);
		$this->speed = new AbilityStatus(1.35);
	}

	protected function onActivate(): ActionResult {
		$motion = VectorUtil::getDirectionHorizontal($this->player->getLocation()->yaw)->multiply($this->speed->get());
		$this->player->setMotion($motion);

		$std = new \stdClass;
		$std->tick = 0;
		$std->last = $this->player->getPosition();

		$this->active = true;
		PlayerUtil::broadcastSound($this->player, "item.trident.riptide_1", 1.71, 0.7);

		TaskUtil::repeatingClosureFailure(function (Closure $fail) use ($motion, $std): void {
			$std->tick++;
			$this->player->setMotion($motion->multiply($this->speed->get() * (1.0 - $std->tick / 20)));

			if ($std->tick > 20) {
				$this->active = false;
				$fail();
			}


			$start = $std->last->add(0, 1.5, 0);
			$end = $this->player->getPosition()->add(0, 1.5, 0);
			foreach ($this->player->getWorld()->getEntities() as $entity) {
				$result = $entity->getBoundingBox()->expandedCopy(0.25, 0.25, 0.25)->calculateIntercept($start, $end);
				if ($result instanceof RayTraceResult) {
					$success = false;
					foreach (EntityUtil::getWithinRange(Position::fromObject($result->getHitVector(), $entity->getWorld()), $this->area->get(), $this->player) as $target) {
						if (MonsterData::isMonster($target)) {
							$source = new EntityDamageByEntityEvent($this->player, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->damage->get());
							$source->setAttackCooldown(0);
							$perc = $this->percentage->get();
							EntityUtil::attackEntity($source, 1.0 * $perc, 1.5 * $perc);
							EntityUtil::immobile($target, (int) $this->duration->get());
							if (!$source->isCancelled()) {
								$success = true;
							}
						}
					}

					if ($success) {
						PlayerUtil::broadcastSound($this->player, "item.trident.return", 2.3, 0.5);
						if (!$this->signal->has(self::SIGNAL_PENETRATE)) {
							$this->player->setMotion(new Vector3(0, 0, 0));
							$this->active = false;
							$fail();
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
