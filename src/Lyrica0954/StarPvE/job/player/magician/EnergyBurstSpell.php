<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\magician;

use Closure;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;

class EnergyBurstSpell extends AbilitySpell {

	public function getActivateItem(): Item {
		return VanillaItems::NETHER_STAR()->setCustomName("§9§lエネルギーバースト");
	}

	public function getName(): string {
		return "エネルギーバースト";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf('§b効果§f: 視線の方向 %1$s に %2$s ダメージを与えるエネルギーを連続して放出する。
§b効果時間§f: %3$s', $area, $damage, $duration);
	}

	protected function onActivate(): ActionResult {
		$this->active = true;
		$expand = 0.5;

		$std = new \stdClass;
		$std->tick = 0;
		$handler = TaskUtil::repeatingClosureFailure(function (Closure $fail) use ($expand, $std): void {
			$success = false;
			$std->tick += 2;
			if ($std->tick > $this->duration->get()) {
				$this->active = false;
				$fail();
			}

			$dir = $this->player->getDirectionVector();
			$start = $this->player->getEyePos()->addVector($dir->multiply($expand));
			$end = $start->addVector($dir->multiply($this->area->get()));

			foreach ($this->player->getWorld()->getEntities() as $entity) {
				if (MonsterData::isMonster($entity) && $entity->isAlive() && !$entity->isClosed()) {
					$result = $entity->getBoundingBox()->expandedCopy($expand, $expand, $expand)->calculateIntercept($start, $end);
					if ($result instanceof RayTraceResult) {
						$source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageEvent::CAUSE_MAGIC, $this->damage->get(), [], 0);
						$source->setAttackCooldown(0);
						$entity->attack($source);

						if (!$source->isCancelled()) {
							$success = true;
						}
					}
				}
			}

			if ($success) {
				PlayerUtil::broadcastSound($this->player, "block.chorusflower.grow", 2.0, 1.0);
			}
		}, 2);

		TaskUtil::repeatingClosureCheck(function () {
			PlayerUtil::broadcastSound($this->player, "power.off.sculk_sensor", 2.0, 0.1);
		}, 10, function () use ($handler) {
			return !$handler->isCancelled();
		});

		return ActionResult::SUCCEEDED();
	}

	protected function init(): void {
		$this->area = new AbilityStatus(1.75);
		$this->damage = new AbilityStatus(1);
		$this->duration = new AbilityStatus(5 * 20);
		$this->cooltime = new AbilityStatus(16 * 20);
	}
}
