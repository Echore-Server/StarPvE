<?php 

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

use Closure;
use Lyrica0954\StarPvE\data\player\PlayerDataCollector;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\shop\content\ArmorUpgradeContent;
use Lyrica0954\StarPvE\game\shop\content\ItemContent;
use Lyrica0954\StarPvE\game\shop\content\SwordUpgradeContent;
use Lyrica0954\StarPvE\game\shop\Shop;
use Lyrica0954\StarPvE\game\stage\Lane;
use Lyrica0954\StarPvE\game\wave\CustomWaveStart;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\game\wave\WaveData;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\task\CooltimeHolder;
use Lyrica0954\StarPvE\task\TaskHolder;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Location;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;



class Game implements CooltimeAttachable{
    use CooltimeHolder;

    const STATUS_STARTING = 0;
    const STATUS_IDLE = 1;
    const STATUS_PLAYING = 2;
    const STATUS_ENDING = 3;
    const STATUS_PREPARE = 4;

    protected World $world;
    protected int $status;

    protected Position $centerPos;
    protected ?Villager $villager;
    protected WaveController $waveController;
    protected Shop $shop;

    public Lane $lane1;
    public Lane $lane2;
    public Lane $lane3;
    public Lane $lane4;

    protected bool $closed;

    public static function statusAsText(int $status){
        $text = match($status) {
            self::STATUS_STARTING => "§6[Starting]",
            self::STATUS_IDLE => "§a[Waiting]",
            self::STATUS_PLAYING => "§c[Playing]",
            self::STATUS_ENDING => "§6[Ended]",
            self::STATUS_PREPARE => "§d[Prepare]",
            default => "Unknown"
        };
        return $text;
    }

    public function __construct(World $world){
        $this->world = $world;
        $this->status = self::STATUS_PREPARE;
        $this->centerPos = new Position(-49.5, 48.6, -49.5, $world);
        $this->villager = null;

        $this->shop = new Shop;
        $this->shop->addContent(new SwordUpgradeContent("武器の強化"));
        $this->shop->addContent(new ArmorUpgradeContent("防具の強化"));
        $f = ItemFactory::getInstance();
        $this->shop->addContent(new ItemContent("パン x4", $f->get(ItemIds::BREAD, 0, 4), $f->get(ItemIds::EMERALD, 0, 10)));

        $this->createCooltimeHandler("Game Tick", CooltimeHandler::BASE_SECOND, 1);

        $this->lane1 = new Lane(new Position(-49.5, 48, -21.5, $world), $this->centerPos);
        $this->lane2 = new Lane(new Position(-77.5, 48, -49.5, $world), $this->centerPos);
        $this->lane3 = new Lane(new Position(-49.5, 48, -77.5, $world), $this->centerPos);
        $this->lane4 = new Lane(new Position(-21.5, 48, -49.5, $world), $this->centerPos);
        $this->maxPlayers = 5;

        $this->closed = false;

        $defaultTitleFormat = "§c§lWave %d";
        $this->waveController = new WaveController($this, [
            1 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 2)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 1),
                    new MonsterData(MonsterData::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 2)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ATTACKER, 1),
                    new MonsterData(MonsterData::ZOMBIE, 1)
                )
            ),
            2 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 3),
                    new MonsterData(MonsterData::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 2),
                    new MonsterData(MonsterData::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 2)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ATTACKER, 1),
                    new MonsterData(MonsterData::ZOMBIE, 3)
                )
            ), 
            3 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 4),
                    new MonsterData(MonsterData::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 7),
                    new MonsterData(MonsterData::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 3),
                    new MonsterData(MonsterData::CREEPER, 1) #NEW: CREEPER
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ATTACKER, 1),
                    new MonsterData(MonsterData::ZOMBIE, 2)
                )
            ), 
            4 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 4),
                    new MonsterData(MonsterData::CREEPER, 2)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 6),
                    new MonsterData(MonsterData::ATTACKER, 3),
                    new MonsterData(MonsterData::CREEPER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 3)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 2)
                )
            ), 
            5 => new WaveData(
                $defaultTitleFormat,
                new CustomWaveStart(function (WaveController $wc){
                    $wc->getGame()->broadcastMessage("§l§cゾンビの群れがレーン §e3 §cに接近中です！！");
                }),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 6),
                    new MonsterData(MonsterData::ATTACKER, 2),
                    new MonsterData(MonsterData::CREEPER, 2)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 3),
                    new MonsterData(MonsterData::ATTACKER, 2)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 17)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ATTACKER, 1),
                    new MonsterData(MonsterData::ZOMBIE, 4),
                    new MonsterData(MonsterData::CREEPER, 2)
                )
            ), 
            6 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 9),
                    new MonsterData(MonsterData::CREEPER, 3),
                    new MonsterData(MonsterData::ATTACKER, 1),
                    new MonsterData(MonsterData::HUSK, 1), #NEW: HUSK

                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 3),
                    new MonsterData(MonsterData::ATTACKER, 1),
                    new MonsterData(MonsterData::CREEPER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 4),
                    new MonsterData(MonsterData::ATTACKER, 1),
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 5),
                    new MonsterData(MonsterData::HUSK, 1),
                    new MonsterData(MonsterData::ATTACKER, 2),
                )
            ), 
            7 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 9),
                    new MonsterData(MonsterData::ATTACKER, 5),
                    new MonsterData(MonsterData::HUSK, 3),

                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 5),
                    new MonsterData(MonsterData::CREEPER, 3)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 4),
                    new MonsterData(MonsterData::ATTACKER, 2)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 5),
                    new MonsterData(MonsterData::HUSK, 1)
                )
            ), 
            8 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 12),
                    new MonsterData(MonsterData::ATTACKER, 4),
                    new MonsterData(MonsterData::HUSK, 3),

                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 5),
                    new MonsterData(MonsterData::ATTACKER, 1),
                    new MonsterData(MonsterData::SPIDER, 1) #NEW: SPIDER
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 4),
                    new MonsterData(MonsterData::ATTACKER, 2),
                    new MonsterData(MonsterData::SPIDER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(MonsterData::ZOMBIE, 5),
                    new MonsterData(MonsterData::HUSK, 1)
                )
            ),  
        ]);
    }

    public function getShop(): Shop{
        return $this->shop;
    }

    public function getVillagerHealth(){
        return $this->villager->getHealth();
    }

    public function getVillager(): ?Villager{
        return $this->villager;
    }

    public function setVillagerHealth(float $health){
        if ($health > $this->villager->getMaxHealth()){
            $this->villager->setMaxHealth((integer) ceil($health));
        }
        $this->villager->setHealth($health);
    }

    public function getStatus(){
        return $this->status;
    }

    public function isClosed(){
        return $this->closed;
    }

    public function getPlayers(){
        return $this->world->getPlayers();
    }

    public function hasMinPlayer(){
        return count($this->getPlayers()) >= 1;
    }

    public function getCenterPosition(){
        return $this->centerPos;
    }

    public function getMaxPlayers(){
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers){
        $this->maxPlayers = $maxPlayers;
    }

    public function canJoin(?Player $player){ #player引数を設定しているのはpartyゲームや追放機能追加のため
        return !$this->closed && $this->status === self::STATUS_IDLE && count($this->getPlayers()) < $this->getMaxPlayers();
    }

    public function broadcastMessage(string|Translatable $message){
        foreach($this->getWorld()->getPlayers() as $player){
            $player->sendMessage($message);
        }
    }

    public function broadcastActionBarMessage(string $message){
        foreach($this->getWorld()->getPlayers() as $player){
            $player->sendActionBarMessage($message);
        }
    }

    public function broadcastTitle(string $title, string $subtitle = ""){
        foreach($this->getWorld()->getPlayers() as $player){
            $player->sendTitle($title, $subtitle);
        }
    }

    public function broadcastTip(string $message){
        foreach($this->getWorld()->getPlayers() as $player){
            $player->sendTip($message);
        }
    }

    public function onPlayerJoin(Player $player){
        if ($player->getWorld() === $this->world){
            $this->log("§a{$player->getName()} has joined the game!");
            $this->broadcastMessage("§a{$player->getName()} がゲームに参加しました！");
        }
    }

    public function onPlayerLeave(Player $player){
        if ($player->getWorld() !== $this->world){
            $this->log("§c{$player->getName()} has left the game");
            $this->broadcastMessage("§c{$player->getName()} がゲームから去りました");
        }

        if (count($this->getPlayers()) <= 0 && !$this->canJoin(null) && !$this->closed){
            $this->end(1*20);
        }
    }
    
    public function finishedPrepare(): void{
        if ($this->status === self::STATUS_PREPARE){
            $this->log("§dGame Created!");

            $this->cooltimeHandler->start(20 * 20);
            $this->status = self::STATUS_IDLE;
        }
    }

    public function getWorld(): World{
        return $this->world;
    }

    public function closeEntities(){
        foreach($this->world->getEntities() as $entity){
            if (!($entity instanceof Player)){
                $entity->close();
            }
        }
    }

    public function gameclear(): void{
        foreach($this->getPlayers() as $player){
            PlayerUtil::playSound($player, "random.totem", volume: 0.5);
            PlayerUtil::reset($player);
            $player->sendTitle("§eGame Clear", "§7あなたは英雄です！");
            PlayerDataCollector::addGenericDigit($player, "GameWon", 1);
            PlayerDataCollector::addGenericDigit($player, "PlayCount", 1);
        }

        $this->end(10 * 20);
    }

    public function gameover(): void{
        foreach($this->getPlayers() as $player){
            PlayerUtil::reset($player);
            PlayerUtil::playSound($player, "mob.evocation_illager.prepare_wololo", 1.0, 1.0);
            PlayerDataCollector::addGenericDigit($player, "GameLost", 1);
            PlayerDataCollector::addGenericDigit($player, "PlayCount", 1);
        }

        $this->log("§6Game Over...");

        $this->end(15 * 20);
    }

    public function end(int $closeDelay){
        $this->status = self::STATUS_ENDING;
        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (){
            $this->closeEntities();
        }), 5);

        $this->log("§7Closing the game...");
        $this->waveController->demonKill();

        $this->breakCooltimeHandler();

        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (){
            $this->close();
        }), max(6, $closeDelay));
    }

    protected function close(){
        $this->status = self::STATUS_IDLE;
        $this->closed = true;

        $gameManager = StarPvE::getInstance()->getGameManager();
        foreach($this->getPlayers() as $player){
            $gamePlayer = $this->getGamePlayer($player);
            $gamePlayer?->leaveGame();
        }
        $gameManager->cleanGame($this->world->getFolderName());
        $this->log("§dSuccessfly Closed");
    }

    protected function getGamePlayer(Player $player){
        return StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($player);
    }

    public function start(): void{
        $this->status = self::STATUS_STARTING;
        $this->log("Starting Game...");

        $this->cooltimeHandler = new CooltimeHandler("Game Start Tick", CooltimeHandler::BASE_SECOND, 1);
        $this->cooltimeHandler->attach($this);
        $this->cooltimeHandler->start(10 * 20);

        $this->closeEntities();

        foreach($this->getPlayers() as $player){
            $player->sendTitle("ゲームが開始されます");
            $this->getGamePlayer($player)?->refreshEquipment();
            PlayerUtil::playSound($player, "mob.evocation_illager.prepare_summon", 1.4);
        }

        $this->villager = new Villager(new Location(
            $this->centerPos->x,
            $this->centerPos->y,
            $this->centerPos->z,
            $this->world,
            0,
            0
        ));

        $this->setVillagerHealth(100);

        $this->villager->spawnToAll();

    }

    protected function onStarted(): void{
        $this->status = self::STATUS_PLAYING;

        $this->log("§6Game Started!");
        $this->waveController->waveStart();
    }

    public function log(string $message){
        $id = $this->world->getFolderName();
        StarPvE::getInstance()->log("§7[Game - {$id}] §7{$message}");
    }

    public function cooltimeTick(CooltimeHandler $cooltimeHandler, int $remain): bool{
        if ($cooltimeHandler->getId() === "Game Tick"){
            if ($this->hasMinPlayer()){
                if ($cooltimeHandler->getRemain() === $cooltimeHandler->getTime()){
                    $this->log("§7Players Ready!");
                }

                foreach($this->getPlayers() as $player){
                    if ($cooltimeHandler->getRemain() === $cooltimeHandler->getTime()){
                        PlayerUtil::playSound($player, "random.click", 0.75, 1.0);
                    }
                    $player->sendActionBarMessage("人数が揃いました！ 準備しています... (残り {$remain}秒 で開始)");
                }
                return true;
            } else {
                if ($cooltimeHandler->getRemain() < $cooltimeHandler->getTime()){
                    $this->broadcastActionBarMessage("キャンセルされました");
                }
                $cooltimeHandler->reset();
            }
        } elseif ($cooltimeHandler->getId() === "Game Start Tick"){
            foreach($this->getPlayers() as $player){
                $player->sendTitle("§r ", "§c- {$remain} -");
                PlayerUtil::playSound($player, "note.bd", volume: 0.5); #名前指定引数！！いひーｗｗ
            }
            return true;
        }

        return false;
    }


    public function cooltimeFinished(CooltimeHandler $cooltimeHandler): void{
        $this->breakCooltimeHandler();
        if ($cooltimeHandler->getId() === "Game Tick"){
            $this->start();
        } elseif ($cooltimeHandler->getId() === "Game Start Tick"){
            $this->onStarted();
        }
    }
}