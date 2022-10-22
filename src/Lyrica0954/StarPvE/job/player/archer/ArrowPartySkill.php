<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\job\player\archer\entity\ExplodeArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\FreezeArrow;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Bow;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class ArrowPartySkill extends Skill {

	const SIGNAL_ARROW_BOUNCE = 1;
	const SIGNAL_ARROW_ADDTIONAL_DURATION = 2;

	public function getName(): string {
		return "アローエクスプロージョン";
	}

	public function getDescription(): string {
		$amount = DescriptionTranslator::number($this->amount, "発");

		return
			sprintf('§b発動時:§f 自分の周囲に合計 %1$s x §c10§f の爆発矢が発射される。', $amount);
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(40 * 20);
		$this->amount = new AbilityStatus(14);
	}

	protected function onActivate(): ActionResult {
		$amount = (int) ($this->amount->get() * 10);
		$loc = Location::fromObject($this->player->getEyePos(), $this->player->getWorld());

		$ability = $this->job->getAbility();
		$damage = 0;
		$area = 0;
		if ($ability instanceof SpecialBowAbility) {
			$damage = $ability->getExplodeDamage()->get();
			$area = $ability->getArea()->get();
		}

		$total = new \stdClass;
		$total->count = 0;
		$run = function (int $tamount) use ($loc, $area, $damage, $total, $amount) {
			for ($i = 0; $i < $tamount; $i++) {
				if ($total->count > $amount) {
					break;
				}

				$total->count++;
				$yaw = RandomUtil::rand_float(0, 360);
				$pitch = RandomUtil::rand_float(-90, 0);

				$dir = VectorUtil::getDirectionVector($yaw, $pitch);
				$location = clone $loc;
				$location->pitch = $pitch;
				$location->yaw = $yaw;
				$dir->y *= 1.2;

				$entity = new ExplodeArrow($location, $this->player, false);
				$entity->area = $area;
				$entity->areaDamage = $damage;
				$entity->bounceCount += $this->signal->get(self::SIGNAL_ARROW_BOUNCE);
				$entity->setMotion($dir->multiply(0.65));
				$entity->spawnToAll();
			}
		};

		$duration = 10 + $this->signal->get(self::SIGNAL_ARROW_ADDTIONAL_DURATION);
		$step = $amount / $duration;

		$it = 1;
		while ($step < 1.0) {
			$it++;
			$step = $amount / ($duration / $it);
		}

		$diff = $step - floor($step);

		TaskUtil::repeatingClosureLimit(function () use ($amount, $run, $step, $total) {
			$run((int) $step);
		}, $it, $duration + (int) ($diff * $duration));

		$run((int) $step);

		PlayerUtil::broadcastSound($loc, "respawn_anchor.set_spawn", 1.2, 0.6);

		return ActionResult::SUCCEEDED_SILENT();
	}
}
