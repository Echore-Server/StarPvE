<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

use Lyrica0954\BossBar\BossBar;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCollector;
use Lyrica0954\StarPvE\entity\item\MonsterDropItem;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\wave\CustomWaveStart;
use Lyrica0954\StarPvE\game\wave\MonsterAttribute;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\game\wave\WaveData;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\task\CooltimeHolder;
use Lyrica0954\StarPvE\task\TaskHolder;
use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use pocketmine\block\BlockFactory;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;

class WaveController implements CooltimeAttachable, Listener{
    use CooltimeHolder;

    protected array $monsterAttributes;
    protected array $monsterEquipments;

    protected Game $game;

    protected ?WaveData $currentWaveData;

    protected array $waveData;
    protected int $monsterRemain;
    protected int $wave;

    public function __construct(Game $game, array $waveData){
        $this->game = $game;
        $this->waveData = $waveData;
        Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());

        $this->reset();

        $this->currentWaveData = null;

        $f = ItemFactory::getInstance();

        $this->monsterAttributes = [
            MonsterData::ZOMBIE => new MonsterAttribute(24, 4.5, 0.35),
            MonsterData::ATTACKER => new MonsterAttribute(130, 6.0, 0.025),
            MonsterData::CREEPER => new MonsterAttribute(15, 1.0, 0.45),
            MonsterData::SPIDER => new MonsterAttribute(50, 3.0, 0.37),
            MonsterData::HUSK => new MonsterAttribute(55, 8.0, 0.25),
            MonsterData::SKELETON => new MonsterAttribute(50, 2.0, 0.21),
            MonsterData::DEFENDER => new MonsterAttribute(80, 0.5, 0.3),
            MonsterData::ZOMBIE_LORD => new MonsterAttribute(360, 10.0, 0.22)
        ];

        #todo: register 形式にする

        $nullArmor = new ArmorSet(null, null, null, null);
        $this->monsterEquipments = [
            MonsterData::ZOMBIE => new ArmorSet($f->get(ItemIds::IRON_HELMET), $f->get(ItemIds::LEATHER_CHESTPLATE), null, null),
            MonsterData::ATTACKER => clone $nullArmor,
            MonsterData::CREEPER => clone $nullArmor,
            MonsterData::SPIDER => clone $nullArmor,
            MonsterData::DEFENDER => clone $nullArmor,
            MonsterData::SKELETON => new ArmorSet($f->get(ItemIds::LEATHER_HELMET), $f->get(ItemIds::DIAMOND_CHESTPLATE), $f->get(ItemIds::DIAMOND_LEGGINGS), $f->get(ItemIds::LEATHER_BOOTS)),
            MonsterData::HUSK => new ArmorSet($f->get(ItemIds::DIAMOND_HELMET), $f->get(ItemIds::CHAIN_CHESTPLATE), $f->get(ItemIds::CHAIN_LEGGINGS), $f->get(ItemIds::CHAIN_BOOTS)),
            MonsterData::ZOMBIE_LORD => ArmorSet::chainmail(),
        ];

        $f = ItemFactory::getInstance();
        $this->monsterDrops = [
            MonsterData::ZOMBIE => [
                $f->get(ItemIds::EMERALD)
            ],
            MonsterData::ATTACKER => [
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD)
            ],
            MonsterData::CREEPER => [
                $f->get(ItemIds::EMERALD)
            ],
            MonsterData::SPIDER => [
                $f->get(ItemIds::EMERALD)
            ],
            MonsterData::HUSK => [
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::BREAD)
            ],
            MonsterData::SKELETON => [
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD)
            ],
            MonsterData::DEFENDER => [
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD)
            ],
            MonsterData::ZOMBIE_LORD => [
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),
                $f->get(ItemIds::EMERALD),

            ]
        ];

        $this->createCooltimeHandler("Wave Tick", CooltimeHandler::BASE_SECOND, 1);
    }

    public function getGame(): Game{
        return $this->game;
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($entity->getWorld() === $this->game->getWorld()){
            if ($damager instanceof Player && $entity === $this->game->getVillager()){
                $event->cancel();
            }

            if (MonsterData::isMonster($entity)){
                if (MonsterData::equal($entity, MonsterData::ATTACKER)){
                    $event->setKnockBack(0);
                }
                

            }
        }
    }

    public function onEntityDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();

        if ($entity->getWorld() === $this->game->getWorld()){
            if ($entity instanceof Player){
                if ($this->game->getStatus() === Game::STATUS_PLAYING){
                    if ($entity->getHealth() <= $event->getFinalDamage()){
                        $event->setModifier(PHP_INT_MIN, EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN);
                        $entity->setGamemode(GameMode::fromString("3"));
                        $entity->sendTitle("死んでしまった...", "10秒後にリスポーンします");
                        PlayerUtil::flee($entity);
                        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($entity){
                            if (!$this->game->isClosed()){
                                $entity->teleport($this->game->getCenterPosition());
                                $entity->setGamemode(GameMode::fromString("2"));
                                $entity->sendTitle("復活しました！");
                                $entity->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), (6 * 20), 255, false, true));
                            }
                        }), (10 * 20));
                    }
                } else {
                    $event->cancel();
                }
            }
        }
    }

    public function onEntityDeath(EntityDeathEvent $event){
        $entity = $event->getEntity();

        if ($entity->getWorld() === $this->game->getWorld()){
            if ($entity instanceof Villager){
                $this->game->gameover();
                return;
            }


            if ($entity instanceof FightingEntity){

                $ldc = $entity->getLastDamageCauseByPlayer();
                $ldcI = $entity->getLastDamageCause();
                if ($ldcI?->getCause() !== EntityDamageEvent::CAUSE_SUICIDE){
                    if ($ldc instanceof EntityDamageByEntityEvent){
                        $damager = $ldc->getdamager();
                        if ($damager instanceof Player){
                            PlayerUtil::playSound($damager, "random.orb", 1.0, 0.8);
        
                            $waveBase = 1 + floor(($this->wave - 1) / 2);
                            $monsterMultiplier = match(true){
                                MonsterData::equal($entity, MonsterData::ATTACKER) => 3,
                                MonsterData::equal($entity, MonsterData::CREEPER) => 2,
                                MonsterData::equal($entity, MonsterData::SKELETON) => 4,
                                MonsterData::equal($entity, MonsterData::DEFENDER) => 6,
                                MonsterData::equal($entity, MonsterData::ZOMBIE_LORD) => 20,
                                default => 1
                            };
            
                            $gainExp = $waveBase * $monsterMultiplier;
                            $adapt = GenericConfigAdapter::fetch($damager);
                            if ($adapt instanceof GenericConfigAdapter){
                                $adapt->addInt(GenericConfigAdapter::MONSTER_KILLS, 1);
                                $exp = $adapt->addExp($gainExp);
                                $nextExp = $adapt->getConfig()->get(GenericConfigAdapter::NEXT_EXP);
                
                                $par = new FloatingTextParticle("§a+§l{$gainExp}§r§f §7(§a{$exp}§f/§a{$nextExp}§7)", "§c>>> §6{$damager->getName()}");
                                $entity->getWorld()->addParticle($entity->getPosition()->add(0, 1.0, 0), $par);
                                
                                StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($par, $entity){
                                    $par->setInvisible(true);
                                    $entity->getWorld()->addParticle($entity->getPosition()->add(0, 1.0, 0), $par);
                                }), 20);
                            }
                            #$entity->setNameTag("§7Killed by §6{$damager->getName()}");
                            #$entity->setScoreTag("§a+§l{$gainExp}§r§fexp §7(§a{$exp}§f/§a{$nextExp}§7)");   
                        }
                    }
                    $drops = $this->monsterDrops[$entity::class] ?? [];
    
                    #$particle = new BlockBreakParticle(BlockFactory::getInstance()->get(ItemIds::REDSTONE_BLOCK, 0));
                    #$entity->getWorld()->addParticle($entity->getPosition()->add(0, 0.1, 0), $particle);
        
                    $par = new SingleParticle();
                    $pp = $entity->getPosition();
                    $pp->y += 1.5;
                    $par->sendToPlayers($entity->getWorld()->getPlayers(), $pp, "starpve:totem_jet_particle");
                    
                    foreach($drops as $item){
                        $motion = new Vector3(
                            RandomUtil::rand_float(-0.12, 0.12),
                            RandomUtil::rand_float(0.2, 0.45),
                            RandomUtil::rand_float(-0.12, 0.12)
                        );
        
                        $loc = $entity->getLocation();
                        $loc->yaw = lcg_value() * 360;
                        $loc->pitch = 0;
                        $dropItemEntity = new MonsterDropItem($loc, clone $item);
                        $dropItemEntity->setMotion($motion);
                        $dropItemEntity->setPickupDelay(8);
                        if ($item->getId() === ItemIds::BREAD){
                            $dropItemEntity->setSound("block.beehive.enter", 0.9, 0.5);
                        } else {
                            $dropItemEntity->setSound("step.amethyst_block", 2.0, 1.0);
                        }
        
                        $dropItemEntity->spawnToAll();
                    }
        
        
                }

                if ($this->monsterRemain <= 0){
                    $this->game->broadcastMessage("§cUnexpected: monsterRemain が 0以下 です");
                }
    
                $this->monsterRemain = max(0, $this->monsterRemain - 1);

                $per = max(0.0, ($this->monsterRemain / $this->currentWaveData->getMonsterCount()));
                $this->game->getBossBar()->setHealthPercent($per);
                $this->game->getBossBar()->update();
    
                if ($this->monsterRemain == 0){
                    $this->waveClear();
                }
            }
        }
    }

    public function getMonsterAttribute(LivingBase $entity){
        $class = $entity::class;
        $attribute = $this->monsterAttributes[$class] ?? null;
        return $attribute;
    }

    protected function reset(){
        $this->wave = 0;
        $this->monsterRemain = 0;
    }


    public function log(string $message){
        $this->game->log("§7[WaveController] {$message}");
    }

    public function getWave(){
        return $this->wave;
    }

    public function getMaxWave(){
        return count($this->waveData);
    }

    public function getMonsterRemain(){
        return $this->monsterRemain;
    }

    protected function getWaveDataFrom(int $wave): ?WaveData{
        if (isset($this->waveData[$wave])){
            $data = $this->waveData[$wave];
            if ($data instanceof WaveData){
                return $data;
            }
        }
        return null;
    }

    public function waveStart(){
        $this->wave ++;

        $waveData = $this->getWaveDataFrom($this->wave);
        if ($waveData !== null){
            $this->log("§7Wave {$this->wave} Started!");
            if (($customWaveStart = $waveData->getCustomWaveStart()) instanceof CustomWaveStart){
                $c = $customWaveStart->getClosure();
                if ($c instanceof \Closure){
                    ($c)($this);
                }
            }

            foreach($this->game->getPlayers() as $player){
                PlayerUtil::playSound($player, "mob.evocation_illager.prepare_attack");
                $player->sendTitle($waveData->parseTitleFormat($this->wave), "§r ");
            }

            $this->spawnWaveMonster($this->wave);
        }
    }

    public function demonKill(){
        $this->breakCooltimeHandler();
        $this->reset();

        $this->log("§dDemon killed");
    }


    public function spawnMonster(WaveMonsters $monsters, Position $pos, \Closure $hook = null){
        if (!$this->game->isClosed()){
            foreach($monsters->getAll() as $monsterData){
                $this->monsterRemain += $monsterData->count;
            }       
            $monsters->spawnToAll($pos, $this->monsterAttributes, $this->monsterEquipments, $hook);
        } else {
            throw new \Exception("Game is closed: WaveController: spawnMonster called");
        }
    }

    public function spawnWaveMonster(int $wave){
        $waveData = $this->getWaveDataFrom($wave);
        if ($waveData !== null){
            $this->currentWaveData = $waveData;
            $this->spawnMonster($waveData->lane1, $this->game->lane1->getStart(), function(Living $entity){
                if ($entity instanceof Attacker){
                    $this->game->lane1->addAttacker($entity);
                }
            });
            $this->spawnMonster($waveData->lane2, $this->game->lane2->getStart(), function(Living $entity){
                if ($entity instanceof Attacker){
                    $this->game->lane2->addAttacker($entity);
                }
            });
            $this->spawnMonster($waveData->lane3, $this->game->lane3->getStart(), function(Living $entity){
                if ($entity instanceof Attacker){
                    $this->game->lane3->addAttacker($entity);
                }
            });
            $this->spawnMonster($waveData->lane4, $this->game->lane4->getStart(), function(Living $entity){
                if ($entity instanceof Attacker){
                    $this->game->lane4->addAttacker($entity);
                }
            });
        }
    }

    public function waveClear(){
        $nextWave = $this->wave + 1;
        $this->mobRemain = 0;
        $this->log("Wave Cleared!");
        if ($nextWave > $this->getMaxWave()){
            $this->game->gameclear();
        } else {
            foreach($this->game->getPlayers() as $player){
                PlayerUtil::playSound($player, "random.levelup", 1.0, 0.5);
                $player->sendTitle("§eWave Clear!", "§7Next wave in 30 seconds...");
            }

            $this->game->getBossBar()->setHealthPercent(0.0);
            $this->game->getBossBar()->update();

            $this->cooltimeHandler->start(30 * 20);
        }
    }

    public function cooltimeTick(CooltimeHandler $cooltimeHandler, int $remain): bool{
        if ($cooltimeHandler->getId() === "Wave Tick"){
            foreach($this->game->getPlayers() as $player){
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
    
    public function cooltimeFinished(CooltimeHandler $cooltimeHandler): void{
        if ($cooltimeHandler->getId() === "Wave Tick"){
            $this->waveStart();
            $this->game->getBossBar()->setHealthPercent(1.0);
            $this->game->getBossBar()->update();
        }
    }
}