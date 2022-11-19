<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RayTraceEntityResult;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class ArmorGunSpell extends AbilitySpell {

	public function getName(): string {
		return "ヒール";
	}

	public function getDescription(): string {
		$range = DescriptionTranslator::number($this->area, "m");
		return sprintf('§b発動時: §f視線の先の味方プレイヤー1体を §c3♡§f 回復させる。 (最大距離: %1$s)', $range);
	}

	public function getActivateItem(): Item {
		return VanillaItems::FEATHER()->setCustomName("§r§l§a{$this->getName()}");
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(12 * 20);
		$this->area = new AbilityStatus(25);
	}

	protected function onActivate(): ActionResult {
		$results = EntityUtil::getLineOfSight($this->player, $this->area->get(), new Vector3(0.6, 0.6, 0.6));
		usort($results, function (RayTraceEntityResult $a, RayTraceEntityResult $b): int {
			$aDist = $a->getEntity()->getPosition()->distanceSquared($this->player->getPosition());
			$bDist = $b->getEntity()->getPosition()->distanceSquared($this->player->getPosition());

			return $aDist <=> $bDist;
		});

		$found = false;
		foreach ($results as $result) {
			$entity = $result->getEntity();
			if (MonsterData::isActiveAlly($entity) && $entity instanceof Player) {
				$source = new EntityRegainHealthEvent($entity, 6, EntityRegainHealthEvent::CAUSE_MAGIC);
				$entity->heal($source);
				$found = true;

				ParticleUtil::send(
					new LineParticle(EntityUtil::getEyePosition($this->player), 3),
					$entity->getWorld()->getPlayers(),
					EntityUtil::getEyePosition($entity),
					ParticleOption::spawnPacket("starpve:soft_green_gas")
				);

				PlayerUtil::playSound($this->player, "item.trident.return", 1.4, 1.0);
				PlayerUtil::playSound($this->player, "random.glass", 0.9, 0.8);
				break;
			}
		}

		if (!$found) {
			$this->cooltimeHandler->setRemain($this->cooltimeHandler->calculate(20));
			return ActionResult::FAILED();
		}

		return ActionResult::SUCCEEDED();
	}
}
