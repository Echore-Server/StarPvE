<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Closure;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\monster\Enderman;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\task\TaskHolder;
use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\Random;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\item\Armor;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\Position;

class WaveMonsters {

	/**
	 * @var MonsterData[]
	 */
	private array $monsters;

	public function __construct(MonsterData ...$monsters) {
		$this->monsters = $monsters;
	}

	/**
	 * @return MonsterData[]
	 */
	public function getAll(): array {
		return $this->monsters;
	}

	public function log(string $message) {
		StarPvE::getInstance()->log("§7[WaveMonsters] {$message}");
	}

	/**
	 * @param Position $pos
	 * @param MonsterOption[] $optionMap
	 * @param \Closure|null $hook
	 * 
	 * @return TaskHandler[]
	 */
	public function spawnToAll(Position $pos, array $optionMap, \Closure $hook = null): array {
		$tasks = [];
		foreach ($this->monsters as $monster) {
			$class = $monster->name;
			$time = 120 * 20;
			$period = (int) floor($time / $monster->count);
			$option = $optionMap[$class];
			if (!$option instanceof MonsterOption) {
				throw new \Exception("Option not registered: {$class}");
			}
			$attribute = $option->getAttribute();
			$equipment = $option->getEquipment();
			$handler = StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask(new class($monster, $pos, $attribute, $equipment, $hook) extends Task {

				private MonsterData $monster;
				private MonsterAttribute $attribute;
				private ArmorSet $equipment;
				private Position $pos;
				private ?\Closure $hook;
				private int $count;

				public function __construct(MonsterData $monster, Position $pos, MonsterAttribute $attribute, ArmorSet $equipment, ?\Closure $hook) {
					$this->monster = $monster;
					$this->attribute = $attribute;
					$this->equipment = $equipment;
					$this->pos = $pos;
					$this->hook = $hook;
					$this->count = 0;
				}

				public function onRun(): void {
					if ($this->pos->world !== null && $this->pos->world?->isLoaded()) {
						$this->count++;
						$class = $this->monster->name;
						$loc = new Location($this->pos->x, $this->pos->y, $this->pos->z, $this->pos->getWorld(), 0, 0);
						$entity = new $class($loc);
						if ($entity instanceof Entity) {
							$size = $entity->size;
							$this->attribute->apply($entity);
							$this->equipment->setUnbreakable(true);
							$this->equipment->equip($entity);
							$defAnimation = new SpawnAnimation(function () {
								return false;
							}, 1);
							$defAnimation->setInitiator(function (Living $entity) {
								$pos = clone $this->pos;
								$d = (1.5 - ($entity->size->getWidth()));
								if ($d < 0) {
									$d = 0;
								}
								$pos->x += RandomUtil::rand_float(-$d, $d);
								$pos->z += RandomUtil::rand_float(-$d, $d);
								$entity->teleport($pos);

								$world = $pos->getWorld();
								$game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($world);
								if ($game instanceof Game) {
									if ($entity instanceof Enderman) {
										$entity->teleport($game->getCenterPosition());
										PlayerUtil::broadcastSound($game->getCenterPosition(), "mob.shulker.teleport", 1.2, 1.0);
									}
								}
								if ($entity instanceof LivingBase) {
									$entity->setImmune(true);

									TaskUtil::delayed(new ClosureTask(function () use ($entity) {
										$entity->setImmune(false);
									}), 10);
								}
							});
							$animation = $this->monster->animation ?? $defAnimation;
							$animation->spawn($entity);
							if ($this->hook !== null) {
								$h = $this->hook;
								$h($entity);
							}
							if ($this->count >= $this->monster->count) {
								$this->getHandler()->cancel();
								StarPvE::getInstance()->log("§7[WaveMonsters] Removed Monster Spawner: Successfly Spawned");
							}
						}
					} else {
						$this->getHandler()->cancel();
						StarPvE::getInstance()->log("§7[WaveMonsters] Removed Monster Spawner: World Unloaded");
					}
				}
			}, $period);
			$tasks[] = $handler;
			$this->log("Added Monster Spawner");
		}

		return $tasks;
	}
}
