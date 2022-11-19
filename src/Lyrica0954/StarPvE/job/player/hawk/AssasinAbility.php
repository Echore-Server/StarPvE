<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\hawk;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\color\Color;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class AssasinAbility extends Ability implements Listener {

	/**
	 * @var Entity[]
	 */
	protected array $targets;

	public function getName(): string {
		return "グレイズ";
	}

	public function getDescription(): string {
		$duration = DescriptionTranslator::second($this->duration);
		$areaDegree = DescriptionTranslator::number($this->area, "°");
		return sprintf('§b発動時:§f %1$s 正面の敵を押しのけながら攻撃をブロックする。
§b範囲:§f %2$s
壁に押し付けるとダメージを与えることができる。
ダメージやノックバックの強さは移動速度に比例する。', $duration, $areaDegree);
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(11 * 20);
		$this->duration = new AbilityStatus(4.25 * 20);
		$this->area = new AbilityStatus(20);
	}

	protected function onActivate(): ActionResult {
		$this->active = true;
		EntityUtil::slowdown($this->player, (int) $this->duration->get(), 0.8, SlowdownRunIds::get($this::class));

		$color = new Color(0, 20, 255, 240);
		$std = new \stdClass;
		$std->tick = 0;
		$duration = (int) $this->duration->get();
		$area = $this->area->get();

		TaskUtil::repeatingClosureLimit(function () use ($color, $std, $duration, $area) {
			$plane = $this->player->getDirectionPlane();
			$plane3 = new Vector3($plane->x, 0, $plane->y);
			$std->tick++;

			$remain = $duration - $std->tick;
			$remainOverall = ($remain / $duration);

			if ($std->tick > ($duration - 50)) {
				$remainPerc = 1.0 - ($remain / 50);
				$color = new Color((int) ($remainPerc * 255), 20, (int) (255 - ($remainPerc * 255)), 240);
			} else {
				$color = new Color(0, 20, 255, 240);

				PlayerUtil::broadcastSound($this->player, "beacon.ambient", 1.8, 0.4);
			}

			if ($std->tick % 20 === 0 || $std->tick === 1) {
				PlayerUtil::broadcastSound($this->player, "beacon.deactivate", 1.5 + ($remainOverall * 1.5));
			}

			$molang = ParticleUtil::circleMolang(2 / 20, 50, $area / 10, $color, $plane3);

			ParticleUtil::send(
				new SingleParticle,
				$this->player->getWorld()->getPlayers(),
				Position::fromObject($plane3->multiply(1.5), $this->player->getWorld()),
				ParticleOption::spawnPacket("starpve:circle", MolangUtil::encode($molang), $this->player->getId())
			);

			$this->targets = [];

			$motionLength =  $this->player->getMovementSpeed() * 6;
			$center = new Vector2($this->player->getPosition()->x, $this->player->getPosition()->z);

			foreach (EntityUtil::getEntitiesWithinFan($center, $this->player->getWorld(), $area, 6, $this->player->getDirectionPlane()) as $entity) {
				if (MonsterData::isMonster($entity)) {
					$motion = EntityUtil::modifyKnockback($entity, $this->player, 1.4 + $motionLength, 0.0);
					EntityUtil::setMotion($entity, $motion);

					$this->targets[spl_object_hash($entity)] = $entity;

					if ($entity->isCollidedHorizontally) {
						$this->player->setMotion((new Vector3(-$plane->x, 0, -$plane->y))->multiply(0.005));

						$source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, ($motionLength * 2), [], 0);
						$source->setAttackCooldown(0);
						$entity->attack($source);
					}
				}
			}
		}, 1, $duration, function () {
			$this->targets = [];
			$this->active = false;
		});

		return ActionResult::SUCCEEDED();
	}

	public function onDamage(EntityDamageByEntityEvent $event): void {
		$damager = $event->getDamager();
		$entity = $event->getEntity();

		if ($entity === $this->player) {
			if (isset($this->targets[spl_object_hash($damager)])) {
				$event->cancel();
			}
		}
	}
}
