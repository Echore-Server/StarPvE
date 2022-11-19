<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\color\Color;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class ConcordeSkill extends Skill {

	/**
	 * @var AbilityStatus
	 */
	protected AbilityStatus $heal;

	public function getName(): string {
		return "フュージョン";
	}

	public function getDescription(): String {
		$area = DescriptionTranslator::number($this->area, "m");
		$heal = DescriptionTranslator::health($this->heal);
		return
			sprintf('§b発動時:§f %1$s 以内の味方に衝撃吸収ハート %2$s を与える。
', $area, $heal);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(12.0);
		$this->heal = new AbilityStatus(35 * 2);
		$this->cooltime = new AbilityStatus(100 * 20);
	}

	public function getHeal(): AbilityStatus {
		return $this->heal;
	}

	protected function onActivate(): ActionResult {
		$heal = $this->heal->get();
		foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
			if (MonsterData::isActiveAlly($entity) && $entity instanceof Living) {
				EntityUtil::absorption($entity, $heal);
			}
		}


		ParticleUtil::send(
			new SingleParticle,
			$this->player->getWorld()->getPlayers(),
			Position::fromObject(
				$this->player->getPosition()->add(0, 0.25 + 2, 0),
				$this->player->getWorld()
			),
			ParticleOption::spawnPacket(
				"starpve:inwards_circle",
				MolangUtil::encode(ParticleUtil::motionCircleMolang(
					ParticleUtil::circleMolang(
						40 * 0.05,
						120,
						$this->area->get(),
						new Color(
							165,
							65,
							0,
							150
						),
						new Vector3(
							0,
							1,
							0
						)
					),
					0,
					0,
					-2
				)),
			)
		);
		return ActionResult::SUCCEEDED();
	}
}
