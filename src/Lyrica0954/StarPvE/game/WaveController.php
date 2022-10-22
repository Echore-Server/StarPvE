<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

use Lyrica0954\BossBar\BossBar;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\Service\Service;
use Lyrica0954\Service\ServiceSession;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\StarPvE\constant\Formats;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCollector;
use Lyrica0954\StarPvE\entity\item\MonsterDropItem;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\event\game\wave\WaveMonsterSpawnEvent;
use Lyrica0954\StarPvE\event\game\wave\WaveStartEvent;
use Lyrica0954\StarPvE\event\PlayerDeathOnGameEvent;
use Lyrica0954\StarPvE\event\PlayerRespawnOnGameEvent;
use Lyrica0954\StarPvE\form\PerkIdentitiesForm;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\game\wave\CustomWaveStart;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterAttribute;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\game\wave\MonsterFactory;
use Lyrica0954\StarPvE\game\wave\MonsterOption;
use Lyrica0954\StarPvE\game\wave\WaveData;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\service\PlayerCounterService;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\task\CooltimeHolder;
use Lyrica0954\StarPvE\task\TaskHolder;
use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\block\BlockFactory;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\utils\EnumTraitTest;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;

class WaveController implements CooltimeAttachable, Listener {
	use CooltimeHolder;

	/**
	 * @var MonsterOption[]
	 */
	protected array $monsterOptions;

	protected Game $game;

	protected ?WaveData $currentWaveData;

	protected array $waveData;
	protected int $monsterRemain;
	protected int $wave;

	protected ServiceSession $serviceSession;

	protected PlayerCounterService $killCounter;

	protected PlayerCounterService $damageCounter;

	/**
	 * @var PlayerCounterService[]
	 */
	protected array $eachDamageCounters;

	/**
	 * @var TaskHandler[]
	 */
	protected array $spawnTasks;

	public float $expMultiplier = 1.0;

	public function __construct(Game $game, array $waveData) {
		$this->game = $game;
		$this->waveData = $waveData;
		Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());

		$this->reset();

		$this->currentWaveData = null;

		$f = ItemFactory::getInstance();

		$this->monsterOptions = MonsterFactory::getInstance()->getList();

		$this->createCooltimeHandler("Wave Tick", CooltimeHandler::BASE_SECOND, 1);
		$this->serviceSession = new ServiceSession(StarPvE::getInstance());
		$this->killCounter = new PlayerCounterService($this->serviceSession);
		$this->damageCounter = new PlayerCounterService($this->serviceSession);
		foreach (MonsterFactory::getInstance()->getClasses() as $class) {
			$this->eachDamageCounters[$class] = new PlayerCounterService($this->serviceSession);
		}
		$this->serviceSession->add($this->killCounter);
		$this->serviceSession->add($this->damageCounter);
		$this->serviceSession->start();

		$this->spawnTasks = [];

		$this->expMultiplier = $game->getOption()->getXpMultiplier();
	}

	public function getKillCounter(): PlayerCounterService {
		return $this->killCounter;
	}

	public function getDamageCounter(): PlayerCounterService {
		return $this->damageCounter;
	}

	public function getEachDamageCounter(string $class): ?PlayerCounterService {
		return $this->eachDamageCounters[$class] ?? null;
	}

	public function getGame(): Game {
		return $this->game;
	}

	/**
	 * @return TaskHandler[]
	 */
	public function getSpawnTasks(): array {
		return $this->spawnTasks;
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return [type]
	 * 
	 * 
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) {
		$entity = $event->getEntity();
		$damager = $event->getDamager();

		if ($entity->getWorld() === $this->game->getWorld()) {
			if (!$damager instanceof Attacker && $entity === $this->game->getVillager()) {
				$event->cancel();
			}

			if (MonsterData::isMonster($entity)) {
				if (MonsterData::equal($entity, DefaultMonsters::ATTACKER)) {
					$event->setKnockBack(0);
				}
			}
		}
	}


	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function monitorEntityDamageByEntity(EntityDamageByEntityEvent $event): void {
		$damager = $event->getDamager();
		$entity = $event->getEntity();
		if ($entity->getWorld() === $this->game->getWorld()) {
			if ($damager instanceof Player) {
				$counter = $this->getDamageCounter();
				$counter->add($damager, (int) $event->getFinalDamage());

				$counter = $this->getEachDamageCounter($entity::class);
				if ($counter instanceof PlayerCounterService) {
					$counter->add($damager, (int) $event->getFinalDamage());
				}
			}
		}
	}

	public function onEntityDamageByChild(EntityDamageByChildEntityEvent $event) {
	}

	public function onEntityDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();

		if ($entity->getWorld() === $this->game->getWorld()) {
			if ($entity instanceof Player) {
				if ($this->game->getStatus() === Game::STATUS_PLAYING) {
					$forceKill = false;
					if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
						$forceKill = true;
					}
					if ($entity->getHealth() <= $event->getFinalDamage() || $forceKill) {
						$event->setModifier(PHP_INT_MIN, EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN);
						$ev = new PlayerDeathOnGameEvent($entity);
						$ev->call();
						if (!$ev->isCancelled()) {
							$this->getGame()->broadcastMessage("§7{$entity->getName()} §fは モンスターに やられてしまった");
							$entity->setGamemode(GameMode::fromString("3"));
							$entity->sendTitle("死んでしまった...", "14秒後にリスポーンします");
							GenericConfigAdapter::fetch($entity)?->addInt(GenericConfigAdapter::DEATHS, 1);
							JobConfigAdapter::fetchCurrent($entity)?->addInt(JobConfigAdapter::DEATHS, 1);
							PlayerUtil::flee($entity);
							StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($entity) {
								if (!$this->game->isClosed()) {
									$id = $entity->getNetworkSession()->getInvManager()->getWindowId($entity->getInventory());
									if ($id !== null) {
										$pk = ContainerClosePacket::create($id, true);
										$entity->getNetworkSession()->sendDataPacket($pk);
									}
									$entity->teleport($this->game->getCenterPosition());
									$entity->setGamemode(GameMode::fromString("2"));
									$entity->sendTitle("復活しました！");
									$entity->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), (6 * 20), 255, false, true));

									$ev = new PlayerRespawnOnGameEvent($entity);
									$ev->call();
								}
							}), (14 * 20));
						}
					}
				} else {
					$event->cancel();
				}
			} else {

				if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
					$entity->kill();
				}
			}
		}
	}

	public function onEntityDeath(EntityDeathEvent $event) {
		$entity = $event->getEntity();

		if ($entity->getWorld() === $this->game->getWorld()) {
			if ($entity instanceof Villager) {
				$this->game->gameover();
				return;
			}


			if ($entity instanceof FightingEntity) {

				$ldc = $entity->getLastDamageCauseByPlayer();
				$ldcI = $entity->getLastDamageCause();
				if ($ldcI?->getCause() !== EntityDamageEvent::CAUSE_SUICIDE) {
					$dropEntities = [];
					/**
					 * @var MonsterDropItem[] $dropEntities
					 */
					$option = $this->monsterOptions[$entity::class] ?? null;
					if ($option instanceof MonsterOption) {
						foreach ($option->getDrop() as $item) {
							foreach (MonsterDropItem::split($item) as $splitItem) {
								$motion = new Vector3(
									RandomUtil::rand_float(-0.12, 0.12),
									RandomUtil::rand_float(0.2, 0.45),
									RandomUtil::rand_float(-0.12, 0.12)
								);
								$loc = $entity->getLocation();
								$loc->yaw = lcg_value() * 360;
								$loc->pitch = 0;
								$dropItemEntity = new MonsterDropItem($loc, clone $splitItem);
								$dropItemEntity->setMotion($motion);
								$dropItemEntity->setPickupDelay(8);
								if ($splitItem->getId() === ItemIds::BREAD) {
									$dropItemEntity->setSound("block.beehive.enter", 0.9, 0.5);
								} else {
									$dropItemEntity->setSound("step.amethyst_block", 2.0, 1.0);
								}

								$dropItemEntity->spawnToAll();

								$dropEntities[] = $dropItemEntity;
							}
						}
					}

					#$particle = new BlockBreakParticle(BlockFactory::getInstance()->get(ItemIds::REDSTONE_BLOCK, 0));
					#$entity->getWorld()->addParticle($entity->getPosition()->add(0, 0.1, 0), $particle);

					$par = new SingleParticle();
					$pp = $entity->getPosition();
					$pp->y += 1.5;
					ParticleUtil::send($par, $entity->getWorld()->getPlayers(), $pp, ParticleOption::spawnPacket("starpve:totem_jet_particle", ""));

					if ($ldc instanceof EntityDamageByEntityEvent) {
						$damager = $ldc->getDamager();
						if ($damager instanceof Player) {
							foreach ($dropEntities as $dropEntity) {
								$dropEntity->setOwningEntity($damager);
							}

							PlayerUtil::playSound($damager, "random.orb", 1.0, 0.8);

							$waveBase = 1 + floor(($this->wave - 1) / 2);
							$option = $this->monsterOptions[$entity::class] ?? null;
							if ($option instanceof MonsterOption) {
								$dropExp = $option->getExp();
								$gainExp = $waveBase * $dropExp * $this->expMultiplier;
								$adapt = GenericConfigAdapter::fetch($damager);
								$jobAdapt = JobConfigAdapter::fetchCurrent($damager);
								if ($adapt instanceof GenericConfigAdapter) {
									$adapt->addInt(GenericConfigAdapter::MONSTER_KILLS, 1);

									$jobAdapt?->addInt(JobConfigAdapter::MONSTER_KILLS, 1);

									$exp = $adapt->addExp($gainExp);
									$jobExp = $jobAdapt?->addExp($gainExp);

									$nextExp = $adapt->getConfig()->get(GenericConfigAdapter::NEXT_EXP);
									$jobNextExp = $jobAdapt?->getConfig()->get(JobConfigAdapter::NEXT_EXP);

									$genericGet = sprintf(Formats::GET_EXP, $gainExp, $exp, $nextExp);
									$jobGet = sprintf(Formats::GET_EXP, $gainExp, $jobExp, $jobNextExp);
									$par = new FloatingTextParticle("§9[Player] §r{$genericGet}\n§9[Job] §r{$jobGet}", "§c>>> §6{$damager->getName()}");
									$entity->getWorld()->addParticle($entity->getPosition()->add(0, 1.0, 0), $par);

									StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($par, $entity) {
										$par->setInvisible(true);
										$entity->getWorld()->addParticle($entity->getPosition()->add(0, 1.0, 0), $par);
									}), 20);

									$this->getKillCounter()->add($damager);
								}
							}
							#$entity->setNameTag("§7Killed by §6{$damager->getName()}");
							#$entity->setScoreTag("§a+§l{$gainExp}§r§fexp §7(§a{$exp}§f/§a{$nextExp}§7)");   
						}
					}
				}

				$stageEntities = 0;
				foreach ($this->game->getWorld()->getEntities() as $te) {
					if (MonsterData::isMonster($te)) {
						if (!$te->isClosed() && $te->isAlive()) {
							if ($entity !== $te) {
								$stageEntities++;
							}
						}
					}
				}

				#$this->game->broadcastMessage("{$stageEntities}");
				if ($stageEntities <= 0) {
					$this->game->broadcastMessage("§7フィールド上の敵がすべて殲滅されました！次のモンスターが瞬時にスポーンします！");
					#print_r($this->spawnTasks);
					foreach ($this->spawnTasks as $taskHandler) {
						if (!$taskHandler->isCancelled()) {
							$taskHandler->run();
						}
					}
				}

				if ($this->monsterRemain <= 0) {
					$this->game->broadcastMessage("§cUnexpected: monsterRemain が 0以下 です");
				} else {
					$this->monsterRemain = max(0, $this->monsterRemain - 1);

					$per = max(0.0, ($this->monsterRemain / $this->currentWaveData->getMonsterCount()));
					$this->game->getBossBar()->setHealthPercent($per);
					$this->game->getBossBar()->update();

					if ($this->monsterRemain == 0) {
						$this->waveClear();
					}
				}
			}
		}
	}


	protected function reset() {
		$this->wave = 0;
		$this->monsterRemain = 0;
	}


	public function log(string $message) {
		$this->game->log("§7[WaveController] {$message}");
	}

	public function getWave() {
		return $this->wave;
	}

	public function getMaxWave() {
		return count($this->waveData);
	}

	public function getMonsterRemain() {
		return $this->monsterRemain;
	}

	protected function getWaveDataFrom(int $wave): ?WaveData {
		if (isset($this->waveData[$wave])) {
			$data = $this->waveData[$wave];
			if ($data instanceof WaveData) {
				return $data;
			}
		}
		return null;
	}

	public function waveStart() {
		$this->wave++;
		$this->spawnTasks = [];


		$ev = new WaveStartEvent($this->getGame(), $this->wave);
		$ev->call();

		$waveData = $this->getWaveDataFrom($this->wave);
		if ($waveData !== null) {
			$this->log("§7Wave {$this->wave} Started!");
			if (($customWaveStart = $waveData->getCustomWaveStart()) instanceof CustomWaveStart) {
				$c = $customWaveStart->getClosure();
				if ($c instanceof \Closure) {
					($c)($this);
				}
			}

			$hreinforce = $this->getHealthReinforceValue($this->wave);
			$hpercentage = (int) round(($hreinforce - 1.0) * 100);

			$dreinforce = $this->getDamageReinforceValue($this->wave);
			$dpercentage = (int) round(($dreinforce - 1.0) * 100);

			foreach ($this->game->getWorld()->getPlayers() as $player) {
				PlayerUtil::playSound($player, "mob.evocation_illager.prepare_attack");
				$player->sendTitle($waveData->parseTitleFormat($this->wave), "§r ");
				$player->sendMessage("§7モンスター: 攻撃力 §c+{$dpercentage}%§7, 体力 §c+{$hpercentage}%");
			}

			$this->spawnWaveMonster($this->wave);
		}
	}

	public function demonKill() {
		$this->breakCooltimeHandler();
		$this->reset();
		HandlerListManager::global()->unregisterAll($this);
		$this->serviceSession->shutdown();

		$this->log("§dDemon killed");
	}

	public function getHealthReinforceValue(int $wave): float {
		$reinforcePeriod = $wave;
		$reinforce = 1.0 + $reinforcePeriod * 0.2;
		return $reinforce;
	}

	public function getDamageReinforceValue(int $wave): float {
		$reinforcePeriod = $wave;
		$reinforce = 1.0 + $reinforcePeriod * 0.15;
		return $reinforce;
	}

	public function modifySpawnOption(MonsterOption $option): void {
		$att = $option->getAttribute();
		$att->health = (int) round($att->health * $this->getHealthReinforceValue($this->wave));
		$att->damage *= $this->getDamageReinforceValue($this->wave);
	}


	public function spawnMonster(WaveMonsters $monsters, Position $pos, \Closure $hook = null) {
		if (!$this->game->isClosed()) {
			foreach ($monsters->getAll() as $monsterData) {
				$this->monsterRemain += $monsterData->count;
			}

			$opt = [];
			foreach ($this->monsterOptions as $k => $option) {
				$cop = clone $option;
				$this->modifySpawnOption($cop);
				$opt[$k] = $cop;
			}
			$ev = new WaveMonsterSpawnEvent($this->getGame(), $this->wave, $monsters, $pos, $opt);
			$ev->call();
			$tasks = $monsters->spawnToAll($ev->getPosition(), $ev->getOptions(), $hook);
			$this->spawnTasks = array_merge($this->spawnTasks, $tasks);
		} else {
			throw new \Exception("Game is closed: WaveController: spawnMonster called");
		}
	}

	public function spawnWaveMonster(int $wave) {
		$waveData = $this->getWaveDataFrom($wave);
		if ($waveData !== null) {
			$this->currentWaveData = $waveData;
			$this->spawnMonster($waveData->lane1, $this->game->lane1->getStart(), function (Living $entity) {
				if ($entity instanceof Attacker) {
					$this->game->lane1->addAttacker($entity);
				}
			});
			$this->spawnMonster($waveData->lane2, $this->game->lane2->getStart(), function (Living $entity) {
				if ($entity instanceof Attacker) {
					$this->game->lane2->addAttacker($entity);
				}
			});
			$this->spawnMonster($waveData->lane3, $this->game->lane3->getStart(), function (Living $entity) {
				if ($entity instanceof Attacker) {
					$this->game->lane3->addAttacker($entity);
				}
			});
			$this->spawnMonster($waveData->lane4, $this->game->lane4->getStart(), function (Living $entity) {
				if ($entity instanceof Attacker) {
					$this->game->lane4->addAttacker($entity);
				}
			});
		}
	}

	public function waveClear() {
		$nextWave = $this->wave + 1;
		if (true) {
			foreach ($this->game->getPlayers() as $player) {
				$gamePlayer = StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($player);
				if ($gamePlayer instanceof GamePlayer) {
					$gamePlayer->setPerkAvailable($gamePlayer->getPerkAvailable() + 1);
					$player->sendMessage("§7ショップでパークを獲得できます！");
					if ($nextWave % 6 === 0) {
						$gamePlayer->rollMasterySpells();
						$gamePlayer->setMasteryAvailable($gamePlayer->getMasteryAvailable() + 1);
						TaskUtil::delayed(new ClosureTask(function () use ($player, $gamePlayer) {
							$player->sendTitle("§r ", "§dマスタリィを獲得しました！\n4秒後に選択フォームを開きます...");
							TaskUtil::delayed(new ClosureTask(function () use ($gamePlayer, $player) {
								PlayerUtil::playSound($player, "conduit.activate", 1.0, 0.8);
								$gamePlayer->sendMasteryForm();
								$gamePlayer->setMasteryAvailable($gamePlayer->getMasteryAvailable() - 1);
							}), 80);
						}), 20);
					}
				}
			}
		}
		$this->mobRemain = 0;
		$this->log("Wave Cleared!");
		if ($nextWave > $this->getMaxWave()) {
			$this->game->gameclear();
		} else {
			$lastReinforce = $this->getHealthReinforceValue($this->wave);
			$hreinforce = $this->getHealthReinforceValue($nextWave);
			$hpercentage = (int) round(($hreinforce - 1.0) * 100);

			$dreinforce = $this->getDamageReinforceValue($nextWave);
			$dpercentage = (int) round(($dreinforce - 1.0) * 100);

			foreach ($this->game->getWorld()->getPlayers() as $player) {
				PlayerUtil::playSound($player, "random.levelup", 1.0, 0.5);
				$player->sendTitle("§eWave Clear!", "§7Next wave in 30 seconds...");

				$effect = new EffectInstance(VanillaEffects::REGENERATION(), 25 * 20, 0, false);
				$player->getEffects()->add($effect);

				if ($lastReinforce !== $hreinforce) {
					TaskUtil::delayed(new ClosureTask(function () use ($player, $lastReinforce, $hpercentage, $dpercentage) {
						PlayerUtil::playSound($player, "mob.witch.celebrate", 1.0, 0.8);
						$player->sendMessage("§7モンスター: 攻撃力 §c+{$dpercentage}%§7, 体力 §c+{$hpercentage}%");
					}), 20);
				}
			}

			foreach ($this->game->getWorld()->getEntities() as $entity) {
				if ($entity instanceof MonsterDropItem) {
					$owning = $entity->getOwningEntity();
					if ($owning instanceof Player) {
						$entity->kill();
					}
				} elseif (MonsterData::isMonster($entity)) {
					if ($entity->isAlive() && !$entity->isClosed()) {
						$entity->close();
					}
				}
			}

			$this->game->getBossBar()->setColor(BossBarColor::YELLOW);
			$this->game->getBossBar()->setHealthPercent(0.0);
			$this->game->getBossBar()->updateAll();

			$this->cooltimeHandler->start(30 * 20);
		}
	}

	public function cooltimeTick(CooltimeHandler $cooltimeHandler, int $remain): bool {
		if ($cooltimeHandler->getId() === "Wave Tick") {
			foreach ($this->game->getWorld()->getPlayers() as $player) {
				PlayerUtil::playSound($player, "random.click", 1.5, 0.5);
				$player->sendActionBarMessage("次のウェーブまで残り {$remain}秒");
			}
			$per = max(0.0, 1.0 - ($remain / $cooltimeHandler->getTime()));
			$this->game->getBossBar()->setHealthPercent($per);
			$this->game->getBossBar()->update();
			return true;
		}

		return false;
	}

	public function cooltimeFinished(CooltimeHandler $cooltimeHandler): void {
		if ($cooltimeHandler->getId() === "Wave Tick") {
			$this->waveStart();
			$this->game->getBossBar()->setColor(BossBarColor::RED);
			$this->game->getBossBar()->setHealthPercent(1.0);
			$this->game->getBossBar()->updateAll();
		}
	}
}
