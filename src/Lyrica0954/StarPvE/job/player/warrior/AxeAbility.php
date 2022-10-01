<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\warrior;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;

class AxeAbility extends Ability {

	public function getName(): string {
		return "巨大武器";
	}

	public function getDescription(): string {
		return '
視線の先に巨大武器を投げる。
当たった敵に %1$s ダメージと強いノックバックを与える。';
	}

	protected function init(): void {
		$this->damage = new AbilityStatus(4.0);
		$this->amount = new AbilityStatus(1);
		$this->speed = new AbilityStatus(3.0);
		$this->duration = new AbilityStatus(10 * 20);
		$this->cooltime = new AbilityStatus(1 * 20);
	}

	protected function onActivate(): ActionResult {
		$amount = (int) $this->amount->get();
		$yaw = $this->player->getLocation()->getYaw();
		$step = 360 / $amount;
		for ($i = 0; $i < 360; $i += $step) {
			$dyaw = $yaw + $i;
			$dir = VectorUtil::getDirectionHorizontal($dyaw);

			$this->player->sendMessage((string) $dir);

			$molang = [];
			$molang[] = MolangUtil::variable("dx", $dir->x);
			$molang[] = MolangUtil::variable("dy", $dir->y);
			$molang[] = MolangUtil::variable("dz", $dir->z);
			$molang[] = MolangUtil::variable("speed", $this->speed->get());
			$molang[] = MolangUtil::variable("size", 1.25);
			$molang[] = MolangUtil::variable("lifetime", ($this->duration->get() / 20));
			$molang[] = MolangUtil::variable("hasCollision", 0.0);
			$encoded = MolangUtil::encode($molang);
			$current = $this->player->getEyePos()->subtract(0, 0.65, 0);
			ParticleUtil::send(
				new SingleParticle,
				$this->player->getWorld()->getPlayers(),
				Position::fromObject($current, $this->player->getWorld()),
				ParticleOption::spawnPacket("starpve:axe", $encoded)
			);

			TaskUtil::repeating(new class($dir, $current, $this) extends Task {
				protected float $damage = 0.0;
				protected int $tick = 0;
				public function __construct(protected Vector3 $dir, protected Vector3 $current, protected Ability $ability) {
					$this->damage = $ability->getDamage()->get();
				}

				public function onRun(): void {
					$this->tick++;
					$player = $this->ability->getPlayer();
					$pos = Position::fromObject($this->current, $player->getWorld());
					foreach (EntityUtil::getWithinRange($pos, 2.3) as $entity) {
						if (MonsterData::isMonster($entity)) {
							$source = new EntityDamageByEntityEvent($player, $entity, EntityDamageEvent::CAUSE_PROJECTILE, $this->damage);
							EntityUtil::attackEntity($source, 3.0, 1.0);
							if (!$source->isCancelled()) {
								$this->damage *= 1.1;
							}
						}
					}
					$this->current = $this->current->addVector($this->dir->divide(20)->multiply($this->ability->getSpeed()->get()));

					if ($this->tick >= ($this->ability->getDuration()->get())) {
						$this->getHandler()->cancel();
					}
				}
			}, 1);
		}

		return ActionResult::SUCCEEDED();
	}
}
