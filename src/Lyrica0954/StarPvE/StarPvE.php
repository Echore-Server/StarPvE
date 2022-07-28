<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE;

use Lyrica0954\MagicParticle\ParticleHost;
use Lyrica0954\MagicParticle\ParticleSender;
use Lyrica0954\Service\ServiceHost;
use Lyrica0954\StarPvE\command\CommandLoader;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\entity\item\MonsterDropItem;
use Lyrica0954\StarPvE\entity\JobShop;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\GameCreationOption;
use Lyrica0954\StarPvE\game\GameManager;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\monster\boss\Stray;
use Lyrica0954\StarPvE\game\monster\boss\ZombieLord;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\monster\Defender;
use Lyrica0954\StarPvE\game\monster\Enderman;
use Lyrica0954\StarPvE\game\monster\Husk;
use Lyrica0954\StarPvE\game\monster\Piglin;
use Lyrica0954\StarPvE\game\monster\PiglinBrute;
use Lyrica0954\StarPvE\game\monster\Skeleton;
use Lyrica0954\StarPvE\game\monster\Spider;
use Lyrica0954\StarPvE\game\monster\Zombie;
use Lyrica0954\StarPvE\game\player\GamePlayerManager;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\JobManager;
use Lyrica0954\StarPvE\job\player\archer\Archer;
use Lyrica0954\StarPvE\job\player\castle\Castle;
use Lyrica0954\StarPvE\job\player\engineer\Engineer;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\job\player\engineer\entity\ShieldBall;
use Lyrica0954\StarPvE\job\player\fighter\Fighter;
use Lyrica0954\StarPvE\job\player\healer\Healer;
use Lyrica0954\StarPvE\job\player\magician\Magician;
use Lyrica0954\StarPvE\job\player\shaman\Shaman;
use Lyrica0954\StarPvE\job\player\swordman\Swordman;
use Lyrica0954\StarPvE\job\player\tank\Tank;
use Lyrica0954\StarPvE\service\BlockFriendlyFireService;
use Lyrica0954\StarPvE\service\indicator\ExpIndicatorService;
use Lyrica0954\StarPvE\service\indicator\InboundDamageService;
use Lyrica0954\StarPvE\service\indicator\OutboundDamageService;
use Lyrica0954\StarPvE\service\indicator\PlayerHealthIndicatorService;
use Lyrica0954\StarPvE\service\message\GenericLevelupMessageService;
use Lyrica0954\StarPvE\service\message\JobLevelupMessageService;
use Lyrica0954\StarPvE\service\message\PlayerAdviceMessageService;
use Lyrica0954\StarPvE\service\player\PlayerChatService;
use Lyrica0954\StarPvE\utils\BuffUtil;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\block\Gravel;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\upnp\UPnP;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Internet;
use pocketmine\world\World;
use xenialdan\apibossbar\API;

final class StarPvE extends PluginBase {

    private static ?StarPvE $instance = null;

    public static function getInstance(): ?StarPvE {
        return self::$instance;
    }

    public World $map;
    public World $hub;

    private JobManager $jobManager;
    private GameManager $gameManager;
    private GamePlayerManager $gamePlayerManager; #todo: gameManager の中に入れるべき？
    private PlayerDataCenter $playerDataCenter;
    private ServiceHost $serviceHost;
    private ParticleHost $particleHost;

    public function getJobManager(): JobManager {
        return $this->jobManager;
    }

    public function getGameManager(): GameManager {
        return $this->gameManager;
    }

    public function getGamePlayerManager(): gamePlayerManager {
        return $this->gamePlayerManager;
    }

    public function getPlayerDataCenter(): PlayerDataCenter {
        return $this->playerDataCenter;
    }

    public function getServiceHost(): ServiceHost {
        return $this->serviceHost;
    }

    public function getParticleHost(): ParticleHost {
        return $this->particleHost;
    }

    private function registerEntities() {
        $f = EntityFactory::getInstance();

        $f->register(Villager::class, function (World $world, CompoundTag $nbt): Villager {
            return new Villager(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:villager"], EntityLegacyIds::VILLAGER);

        $f->register(JobShop::class, function (World $world, CompoundTag $nbt): JobShop {
            $skinData = file_get_contents($this->getDataFolder() . "JobShopSkin.txt");
            return new JobShop(EntityDataHelper::parseLocation($nbt, $world), new Skin("Standard_Custom", $skinData), $nbt);
        }, ["starpve:job_shop"], EntityLegacyIds::PLAYER);

        $f->register(Zombie::class, function (World $world, CompoundTag $nbt): Zombie {
            return new Zombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:zombie"], EntityLegacyIds::ZOMBIE);

        $f->register(Creeper::class, function (World $world, CompoundTag $nbt): Creeper {
            return new Creeper(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:creeper"], EntityLegacyIds::CREEPER);

        $f->register(Attacker::class, function (World $world, CompoundTag $nbt): Attacker {
            return new Attacker(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:attacker"], EntityLegacyIds::WITCH);

        $f->register(Spider::class, function (World $world, CompoundTag $nbt): Spider {
            return new Spider(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:spider"], EntityLegacyIds::SPIDER);

        $f->register(Husk::class, function (World $world, CompoundTag $nbt): Husk {
            return new Husk(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:husk"], EntityLegacyIds::HUSK);

        $f->register(Skeleton::class, function (World $world, CompoundTag $nbt): Skeleton {
            return new Skeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:skeleton"], EntityLegacyIds::SKELETON);

        $f->register(MemoryEntity::class, function (World $world, CompoundTag $nbt): MemoryEntity {
            return new MemoryEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:memory_entity"], EntityLegacyIds::SNOWBALL);

        $f->register(Defender::class, function (World $world, CompoundTag $nbt): Defender {
            return new Defender(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:defender"], EntityLegacyIds::DROWNED);

        $f->register(Piglin::class, function (World $world, CompoundTag $nbt): Piglin {
            return new Piglin(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:piglin"], EntityLegacyIds::ZOMBIE_PIGMAN);

        $f->register(Enderman::class, function (World $world, CompoundTag $nbt): Enderman {
            return new Enderman(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:enderman"], EntityLegacyIds::ENDERMAN);

        $f->register(PiglinBrute::class, function (World $world, CompoundTag $nbt): PiglinBrute {
            return new PiglinBrute(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:piglin_brute"], EntityLegacyIds::ZOMBIE_PIGMAN);


        $f->register(ZombieLord::class, function (World $world, CompoundTag $nbt): ZombieLord {
            return new ZombieLord(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:zombie_lord_boss"], EntityLegacyIds::ZOMBIE);

        $f->register(Stray::class, function (World $world, CompoundTag $nbt): Stray {
            return new Stray(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["starpve:stray_boss"], EntityLegacyIds::STRAY);


        $f->register(MonsterDropItem::class, function (World $world, CompoundTag $nbt): MonsterDropItem {
            $itemTag = $nbt->getCompoundTag("Item");
            if ($itemTag === null) {
                throw new SavedDataLoadingException("Expected \"Item\" NBT tag not found");
            }

            $item = Item::nbtDeserialize($itemTag);
            if ($item->isNull()) {
                throw new SavedDataLoadingException("Item is invalid");
            }
            return new MonsterDropItem(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
        }, ['starpve:monster_drop_item'], EntityLegacyIds::ITEM);
    }

    protected function onDisable(): void {
        $this->gameManager->cleanAll();
        $this->playerDataCenter->save();
    }

    protected function onLoad(): void {
        self::$instance = $this;
        $wm = $this->getServer()->getWorldManager();
        $wm->loadWorld("map", true);
        $wm->loadWorld("hub", true);

        $this->map = $wm->getWorldByName("map");
        $this->hub = $wm->getWorldByName("hub");

        $this->log("Loading Managers...");
        $this->jobManager = new JobManager();
        $this->gameManager = new GameManager();
        $this->gamePlayerManager = new GamePlayerManager();

        $this->log("Registering Entities...");
        $this->registerEntities();

        $this->gameManager->deleteUnusedWorld();

        $this->log("Loading Commands...");
        CommandLoader::load($this);
    }

    protected function onEnable(): void {
        $this->log("Loading Utilities...");
        (new EntityUtil)->init($this);
        $buffUtil = (new BuffUtil($this));

        $wm = $this->getServer()->getWorldManager();
        $wm->loadWorld("map", true);
        $wm->loadWorld("hub", true);
        $this->map = $wm->getWorldByName("map");
        $this->hub = $wm->getWorldByName("hub");

        $this->log("Registering Jobs...");
        $this->jobManager->register(new Swordman(null));
        $this->jobManager->register(new Magician(null));
        $this->jobManager->register(new Fighter(null));
        $this->jobManager->register(new Engineer(null));
        $this->jobManager->register(new Healer(null));
        $this->jobManager->register(new Shaman(null));
        $this->jobManager->register(new Archer(null));
        $this->jobManager->register(new Tank(null));
        $this->jobManager->register(new Castle(null));


        $this->log("Starting Player Data Center...");
        $this->playerDataCenter = new PlayerDataCenter($this->getDataFolder() . "player_data");

        new EventListener($this);
        $this->log((string) Internet::getInternalIP());

        $this->log("Starting Service Host...");
        $this->serviceHost = new ServiceHost($this);

        $this->log("Starting Particle Host...");
        $this->particleHost = new ParticleHost($this, new ParticleSender());

        $this->log("Opening Service Session...");
        $session = $this->serviceHost->open();

        $this->log("Registering Services...");
        $session->add(new InboundDamageService($session));
        $session->add(new OutboundDamageService($session));
        $session->add(new PlayerHealthIndicatorService($session));
        $session->add(new BlockFriendlyFireService($session));
        $session->add(new GenericLevelupMessageService($session));
        $session->add(new JobLevelupMessageService($session));
        $session->add(new PlayerAdviceMessageService($session));
        $session->add(new ExpIndicatorService($session));
        $session->add(new PlayerChatService($session));

        $this->log("Starting Service Session...");
        $session->start();

        $this->log("Creating New Game...");
        $this->gameManager->createNewGame(GameCreationOption::manual());
    }

    public function log(string $message) {
        $this->getServer()->getLogger()->info("§c[StarPvE] §7{$message}");
    }
}
