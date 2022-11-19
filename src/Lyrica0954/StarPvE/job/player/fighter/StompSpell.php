<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\fighter;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilitySpell;
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
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

class StompSpell extends AbilitySpell {

	public function getName(): string {
		return "コンタクトスマッシュ";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$duration = DescriptionTranslator::second($this->duration);
		return sprintf('§b発動時:§f チャージした後に斧を振りおろし、 %1$s 以内の敵に %2$s のダメージを与え、
%3$s 動けなくさせる。
ヒットさせた敵の数に応じて、一時的な衝撃吸収ハートを獲得する
振り下ろした後は移動速度が上昇する
また、武器にすることもできる。', $area, $damage, $duration);
	}

	public function getActivateItem(): Item {
		return VanillaItems::IRON_AXE()->setUnbreakable()->setCustomName("§r§l§c{$this->getName()}");
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(8 * 20);
		$this->area = new AbilityStatus(2.25);
		$this->damage = new AbilityStatus(3);
		$this->duration = new AbilityStatus(1 * 20);
	}

	protected function onActivate(): ActionResult {
		EntityUtil::slowdown($this->player, 10, 0.375, SlowdownRunIds::get($this::class));

		$pos = $this->player->getPosition();
		$dir = $this->player->getDirectionPlane()->multiply(3);

		$currentTargetPos = Position::fromObject($pos->add($dir->x, 0, $dir->y), $pos->getWorld());

		ParticleUtil::send(
			new SingleParticle,
			$this->player->getWorld()->getPlayers(),
			Position::fromObject(
				new Vector3($dir->x, -$this->player->getEyeHeight() + 0.45, $dir->y),
				$this->player->getWorld()
			),
			ParticleOption::spawnPacket(
				"starpve:inwards_circle",
				MolangUtil::encode(ParticleUtil::motionCircleMolang(
					ParticleUtil::circleMolang(
						11 * 0.05,
						120,
						$this->area->get(),
						new Color(
							125,
							0,
							125,
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
					-1.5
				)),
				$this->player->getId()
			)
		);

		TaskUtil::delayed(new ClosureTask(function () use ($dir) {
			$pos = $this->player->getPosition();

			$targetPos = Position::fromObject($pos->add($dir->x, 0, $dir->y), $pos->getWorld());

			$hits = 0;

			EntityUtil::slowdown($this->player, 30, 1.4, SlowdownRunIds::get($this::class, 1));

			foreach (EntityUtil::getWithinRangePlane(new Vector2($targetPos->x, $targetPos->z), $pos->getWorld(), $this->area->get()) as $entity) {
				if (MonsterData::isMonster($entity)) {
					$source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->damage->get(), [], 0);
					EntityUtil::attackEntity($source, 0.4, 1.4);

					if (!$source->isCancelled()) {
						$hits++;
					}

					EntityUtil::immobile($entity, (int) $this->duration->get());
				}
			}

			$absorption = $hits * 2;

			EntityUtil::absorption($this->player, $absorption, 60);

			$molang = [];
			$molang[] = MolangUtil::variable("lifetime", 1.5);
			$molang[] = MolangUtil::variable("amount", 120);
			$molang[] = MolangUtil::member("color", [
				["r", 1.0],
				["g", 0.2],
				["b", 0.2],
				["a", 0.75]
			]);

			$molang[] = MolangUtil::member("plane", [
				["x", 0.0],
				["y", 1.0],
				["z", 0.0]
			]);


			$molang[] = MolangUtil::variable("radius", $this->area->get());

			PlayerUtil::broadcastSound($targetPos, "random.explode", 0.4, 0.6);
			PlayerUtil::broadcastSound($targetPos, "armor.equip_iron", 0.45);

			ParticleUtil::send(
				new SingleParticle,
				$this->player->getWorld()->getPlayers(),
				Position::fromObject($targetPos->add(0, 0.25, 0), $this->player->getWorld()),
				ParticleOption::spawnPacket("starpve:circle", MolangUtil::encode($molang))
			);
		}), 10);

		return ActionResult::SUCCEEDED();
	}
}
