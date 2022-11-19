<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\hawk;

use Closure;
use Lyrica0954\StarPvE\entity\MotionResistance;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class GrabSpell extends AbilitySpell implements Listener {

	/**
	 * @var Entity[]
	 */
	protected array $grabbing = [];

	public function getName(): string {
		return "わしづかみ";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$duration = DescriptionTranslator::second($this->duration);
		$damage = DescriptionTranslator::health($this->damage);
		return sprintf('§b発動時:§f %1$s 以内の敵を全てつかむ。
つかみ中は移動速度が §c62.5%%%%§f 低下する
つかみ時間(ベース: %2$s) は敵のノックバック耐性やサイズが高い/大きいほど短くなる
§c0.5秒§f ごとに %3$s ダメージを与える。', $area, $duration, $damage);
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(10 * 20);
		$this->duration = new AbilityStatus(2.5 * 20);
		$this->damage = new AbilityStatus(12);
		$this->area = new AbilityStatus(2.0);
	}

	public function getActivateItem(): Item {
		return VanillaItems::BLACK_DYE()->setCustomName("§r§l§8{$this->getName()}");
	}

	protected function onActivate(): ActionResult {
		$targets = array_filter(iterator_to_array(EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get(), $this->player)), function (Entity $entity): bool {
			return MonsterData::isMonster($entity);
		});
		/**
		 * @var Entity[] $targets
		 */
		if (count($targets) > 0) {
			$globalDuration = $this->duration->get();
			EntityUtil::slowdown($this->player, (int) $globalDuration, 0.375, SlowdownRunIds::get($this::class));
			$this->active = true;
			foreach ($targets as $target) {
				$duration = $globalDuration;
				$duration -= ($target->getScale() > 1.0 ? ($target->getScale() - 1.0) * 50 : 0.0);
				$duration -= ($target instanceof MotionResistance ? (($res = 1.0 - $target->getMotionResistance()) > 0.0 ? $res * 24 : 0.0) : 0.0);

				if ($duration < 10) {
					$this->job->getActionListManager()->push(new LineOption("§c一部のモンスターに対してのつかみ時間が0.5秒以下です！"));
					continue;
				}

				$this->grabbing[spl_object_hash($target)] = $target;

				$firstDelta = new Vector3(RandomUtil::rand_float(-0.8, 0.8), 0, RandomUtil::rand_float(-0.8, 0.8));

				$std = new \stdClass;
				$std->tick = 0;

				TaskUtil::repeatingClosureFailure(function (Closure $fail) use ($std, $duration, $target, $firstDelta): void {
					$std->tick++;

					if ($std->tick > $duration || !$target->isAlive() || $target->isClosed()) {
						$fail();
						unset($this->grabbing[spl_object_hash($target)]);
						if (count($this->grabbing) <= 0) {
							$this->active = false;
						}
						return;
					}

					$next = $this->player->getEyePos()->addVector($this->player->getDirectionVector()->multiply(1.15)->add($firstDelta->x, -0.1, $firstDelta->z));
					$delta = $next->subtractVector($target->getPosition());
					$moveMethod = (new \ReflectionClass(Entity::class))->getMethod("move");
					$moveMethod->setAccessible(true);
					$moveMethod->invoke($target, $delta->x, $delta->y, $delta->z);

					if ($std->tick % 10 === 0) {
						$source = new EntityDamageByEntityEvent($this->player, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->damage->get(), [], 0.0);
						$target->attack($source);
					}
				}, 1);
			}

			return ActionResult::SUCCEEDED();
		}

		return ActionResult::MISS();
	}

	public function onDamageByEntity(EntityDamageByEntityEvent $event) {
		if ($event->getEntity() !== $this->player) {
			return;
		}

		if (isset($this->grabbing[spl_object_hash($event->getDamager())])) {
			$event->cancel();
		}
	}
}
