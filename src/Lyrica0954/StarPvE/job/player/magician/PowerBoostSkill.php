<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\magician;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\Position;

class PowerBoostSkill extends Skill {

	protected ?TaskHandler $overdriveTask = null;

	public function getCooltime(): int {
		return (80 * 20);
	}

	public function getName(): String {
		return "パワーブースト";
	}

	public function getDescription(): String {
		$duration = DescriptionTranslator::second($this->duration);
		$percentage = DescriptionTranslator::percentage($this->percentage, false, -1.0);
		return
			sprintf('§b効果時間:§f %1$s
§b効果: §f移動速度が %2$s 上昇する 
さらに効果中はアビリティのクールタイムが §c0.35秒§f にスピードアップする。', $duration, $percentage);
	}

	protected function init(): void {
		$this->duration = new AbilityStatus(12 * 20);
		$this->percentage = new AbilityStatus(1.4);
	}

	protected function onActivate(): ActionResult {
		PlayerUtil::playSound($this->player, "firework.launch");
		$this->active = true;
		$this->player->setMovementSpeed($this->player->getMovementSpeed() * $this->percentage->get());
		StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () {
			PlayerUtil::playSound($this->player, "random.fizz", 0.5);
			$this->active = false;
			$this->overdriveTask?->cancel();
			$this->player->setMovementSpeed($this->player->getMovementSpeed() / $this->percentage->get());
		}), (int) $this->duration->get());

		$this->overdriveTask = StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () {
			ParticleUtil::send(
				new SingleParticle,
				$this->player->getWorld()->getPlayers(),
				Position::fromObject($this->player->getEyePos(), $this->player->getWorld()),
				ParticleOption::spawnPacket("starpve:magician_overdrive")
			);
		}), 6);

		return ActionResult::SUCCEEDED();
	}
}
