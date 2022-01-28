<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE;

use Lyrica0954\StarPvE\command\CommandLoader;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\entity\item\MonsterDropItem;
use Lyrica0954\StarPvE\entity\JobShop;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\GameManager;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\monster\Zombie;
use Lyrica0954\StarPvE\game\player\GamePlayerManager;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\JobManager;
use Lyrica0954\StarPvE\job\player\magician\Magician;
use Lyrica0954\StarPvE\job\player\swordman\Swordman;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\upnp\UPnP;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Internet;
use pocketmine\world\World;

final class StarPvE extends PluginBase {

    private static ?StarPvE $instance = null;

    public static function getInstance(): ?StarPvE{
        return self::$instance;
    }

    public World $map;
    public World $hub;

    private JobManager $jobManager;
    private GameManager $gameManager;
    private GamePlayerManager $gamePlayerManager; #todo: gameManager の中に入れるべき？
    private PlayerDataCenter $playerDataCenter;

    public function getJobManager(): JobManager{
        return $this->jobManager;
    }

    public function getGameManager(): GameManager{
        return $this->gameManager;
    }

    public function getGamePlayerManager(): gamePlayerManager{
        return $this->gamePlayerManager;
    }

    public function getPlayerDataCenter(): PlayerDataCenter{
        return $this->playerDataCenter;
    }

    private function registerEntities(){
        $f = EntityFactory::getInstance();

        $f->register(Villager::class, function (World $world, CompoundTag $nbt): Villager{
            return new Villager(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:villager"], EntityLegacyIds::VILLAGER);

        $f->register(JobShop::class, function (World $world, CompoundTag $nbt): JobShop{
            $skinData = file_get_contents($this->getDataFolder() . "JobShopSkin.txt");
            return new JobShop(EntityDataHelper::parseLocation($nbt, $world), new Skin("Standard_Custom", $skinData), $nbt);
        }, ["starpve:job_shop"], EntityLegacyIds::PLAYER);

        $f->register(Zombie::class, function (World $world, CompoundTag $nbt): Zombie{
            return new Zombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:zombie"], EntityLegacyIds::ZOMBIE);

        $f->register(Creeper::class, function (World $world, CompoundTag $nbt): Creeper{
            return new Creeper(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:creeper"], EntityLegacyIds::CREEPER);

        $f->register(Attacker::class, function (World $world, CompoundTag $nbt): Attacker{
            return new Attacker(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:attacker"], EntityLegacyIds::WITCH);

        $f->register(MonsterDropItem::class, function(World $world, CompoundTag $nbt) : MonsterDropItem{
            $itemTag = $nbt->getCompoundTag("Item");
            if($itemTag === null){
                throw new SavedDataLoadingException("Expected \"Item\" NBT tag not found");
            }
 
            $item = Item::nbtDeserialize($itemTag);
            if($item->isNull()){
                throw new SavedDataLoadingException("Item is invalid");
            }
            return new MonsterDropItem(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
        }, ['starpve:monster_drop_item'], EntityLegacyIds::ITEM);
    }

    protected function onDisable(): void{
        $this->gameManager->cleanAll();
        $this->playerDataCenter->save();
    }

    protected function onLoad(): void{
        self::$instance = $this;

        $this->log("Loading Managers...");
        $this->jobManager = new JobManager();
        $this->gameManager = new GameManager();
        $this->gamePlayerManager = new GamePlayerManager();

        $this->log("Registering Entities...");
        $this->registerEntities();

        $this->log("Registering Jobs...");
        $this->jobManager->register(new Swordman(null));
        $this->jobManager->register(new Magician(null));

        $this->log("Deleting Unused World...");
        $this->gameManager->deleteUnusedWorld();

        $this->log("Loading Commands...");
        CommandLoader::load($this);
    }

    protected function onEnable(): void{
        $wm = $this->getServer()->getWorldManager();
        $wm->loadWorld("map");
        $wm->loadWorld("hub");
        $this->map = $wm->getWorldByName("map");
        $this->hub = $wm->getWorldByName("hub");

        
        $this->log("Starting Player Data Center...");
        $this->playerDataCenter = new PlayerDataCenter($this->getDataFolder() . "player_data");

        new EventListener($this);
        $this->log((string) Internet::getInternalIP());

        $this->log("Creating New Game...");
        $this->gameManager->createNewGame();
    }

    public function log(string $message){
        $this->getServer()->getLogger()->info("§c[StarPvE] §7{$message}");
    }
}