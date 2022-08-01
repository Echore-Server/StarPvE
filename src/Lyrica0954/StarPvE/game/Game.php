<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

use Closure;
use Lyrica0954\BossBar\BossBar;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCollector;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\event\game\GameStartEvent;
use Lyrica0954\StarPvE\game\identity\GameArgIdentity;
use Lyrica0954\StarPvE\game\shop\content\ArmorUpgradeContent;
use Lyrica0954\StarPvE\game\shop\content\ItemContent;
use Lyrica0954\StarPvE\game\shop\content\PerkContent;
use Lyrica0954\StarPvE\game\shop\content\PrestageContent;
use Lyrica0954\StarPvE\game\shop\content\SwordUpgradeContent;
use Lyrica0954\StarPvE\game\shop\Shop;
use Lyrica0954\StarPvE\game\stage\DefaultStages;
use Lyrica0954\StarPvE\game\stage\Lane;
use Lyrica0954\StarPvE\game\stage\StageInfo;
use Lyrica0954\StarPvE\game\wave\CustomWaveStart;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\game\wave\WaveData;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\task\CooltimeHolder;
use Lyrica0954\StarPvE\task\TaskHolder;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;



class Game implements CooltimeAttachable {
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
    protected BossBar $bossBar;

    public Lane $lane1;
    public Lane $lane2;
    public Lane $lane3;
    public Lane $lane4;

    protected StageInfo $stageInfo;

    protected bool $closed;

    /**
     * @var Player[]
     */
    protected array $players;

    public static function statusAsText(int $status) {
        $text = match ($status) {
            self::STATUS_STARTING => "§6[Starting]",
            self::STATUS_IDLE => "§a[Waiting]",
            self::STATUS_PLAYING => "§c[Playing]",
            self::STATUS_ENDING => "§6[End]",
            self::STATUS_PREPARE => "§d[Prepare]",
            default => "Unknown"
        };
        return $text;
    }

    public function __construct(World $world, StageInfo $stageInfo, GameOption $option) {
        $this->world = $world;
        $this->stageInfo = $stageInfo;
        foreach ($stageInfo->getIdentityGroup()->getAll() as $identity) {
            if ($identity instanceof GameArgIdentity) {
                $identity->setGame($this);
            }
        }
        $stageInfo->getIdentityGroup()->apply();
        $this->status = self::STATUS_PREPARE;
        $this->centerPos = Position::fromObject($stageInfo->getCenter(), $world);
        $this->villager = null;

        $this->players = [];

        $this->bossBar = new BossBar("残りモンスター");
        $this->bossBar->setColor(BossBarColor::RED);

        $this->shop = new Shop;
        $this->shop->addContent(new SwordUpgradeContent("武器の強化"));
        $this->shop->addContent(new ArmorUpgradeContent("防具の強化"));
        $f = ItemFactory::getInstance();
        $this->shop->addContent(new ItemContent("パン x4", $f->get(ItemIds::BREAD, 0, 4), $f->get(ItemIds::EMERALD, 0, 10)));
        $this->shop->addContent(new PerkContent("パークの取得"));
        $this->shop->addContent(new PrestageContent("プレステージの実行"));

        $this->createCooltimeHandler("Game Tick", CooltimeHandler::BASE_SECOND, 1);

        $this->lane1 = new Lane(Position::fromObject($stageInfo->getLane1(), $world), $this->centerPos);
        $this->lane2 = new Lane(Position::fromObject($stageInfo->getLane2(), $world), $this->centerPos);
        $this->lane3 = new Lane(Position::fromObject($stageInfo->getLane3(), $world), $this->centerPos);
        $this->lane4 = new Lane(Position::fromObject($stageInfo->getLane4(), $world), $this->centerPos);
        $this->maxPlayers = $option->getMaxPlayers();

        $this->closed = false;

        $defaultTitleFormat = "§c§lWave %d";
        $this->waveController = new WaveController($this, [
            1 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 1)
                )
            ),
            2 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 3),
                    new MonsterData(DefaultMonsters::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 2),
                    new MonsterData(DefaultMonsters::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 3)
                )
            ),
            3 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 4),
                    new MonsterData(DefaultMonsters::ATTACKER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 7),
                    new MonsterData(DefaultMonsters::ATTACKER, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 3),
                    new MonsterData(DefaultMonsters::CREEPER, 1) #NEW: CREEPER
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 2)
                )
            ),
            4 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 5),
                    new MonsterData(DefaultMonsters::CREEPER, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 6),
                    new MonsterData(DefaultMonsters::ATTACKER, 3),
                    new MonsterData(DefaultMonsters::CREEPER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 4)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 3)
                )
            ),
            5 => new WaveData(
                $defaultTitleFormat,
                new CustomWaveStart(function (WaveController $wc) {
                    $wc->getGame()->broadcastMessage("§l§cクリーパーの群れがレーン §e3 §cに接近中です！！");
                    $wc->getGame()->broadcastMessage("§l§cボスがレーン §e3 §cに出現しました！");
                }),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 6),
                    new MonsterData(DefaultMonsters::ATTACKER, 2),
                    new MonsterData(DefaultMonsters::CREEPER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 3),
                    new MonsterData(DefaultMonsters::ATTACKER, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE_LORD, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::CREEPER, 10)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::ZOMBIE, 4),
                    new MonsterData(DefaultMonsters::CREEPER, 1)
                )
            ),
            6 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 9),
                    new MonsterData(DefaultMonsters::CREEPER, 3),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::HUSK, 1), #NEW: HUSK, PIGLIN
                    new MonsterData(DefaultMonsters::PIGLIN, 1)

                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 3),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::CREEPER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 4),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 5),
                    new MonsterData(DefaultMonsters::HUSK, 1),
                    new MonsterData(DefaultMonsters::ATTACKER, 2),
                )
            ),
            7 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters( #NEW: ENDERMAN
                    new MonsterData(DefaultMonsters::ZOMBIE, 9),
                    new MonsterData(DefaultMonsters::ATTACKER, 5),
                    new MonsterData(DefaultMonsters::HUSK, 3),
                    new MonsterData(DefaultMonsters::ENDERMAN, 2)

                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 5),
                    new MonsterData(DefaultMonsters::CREEPER, 3)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 4),
                    new MonsterData(DefaultMonsters::ATTACKER, 2),
                    new MonsterData(DefaultMonsters::PIGLIN, 3)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 5),
                    new MonsterData(DefaultMonsters::HUSK, 1)
                )
            ),
            8 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 12),
                    new MonsterData(DefaultMonsters::ATTACKER, 4),
                    new MonsterData(DefaultMonsters::HUSK, 3),
                    new MonsterData(DefaultMonsters::PIGLIN, 1)

                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 5),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::SPIDER, 1), #NEW: SPIDER
                    new MonsterData(DefaultMonsters::PIGLIN, 2),
                    new MonsterData(DefaultMonsters::ENDERMAN, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 4),
                    new MonsterData(DefaultMonsters::ATTACKER, 2),
                    new MonsterData(DefaultMonsters::SPIDER, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 5),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::PIGLIN, 3),
                    new MonsterData(DefaultMonsters::ENDERMAN, 2)
                )
            ),
            9 => new WaveData(
                $defaultTitleFormat,
                null,
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 13),
                    new MonsterData(DefaultMonsters::ATTACKER, 2),
                    new MonsterData(DefaultMonsters::HUSK, 2),
                    new MonsterData(DefaultMonsters::SPIDER, 3),
                    new MonsterData(DefaultMonsters::PIGLIN, 7),
                    new MonsterData(DefaultMonsters::ENDERMAN, 1)

                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 8),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::SPIDER, 3),
                    new MonsterData(DefaultMonsters::HUSK, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 6),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::SPIDER, 2),
                    new MonsterData(DefaultMonsters::CREEPER, 3),
                    new MonsterData(DefaultMonsters::ENDERMAN, 3)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 3),
                    new MonsterData(DefaultMonsters::ATTACKER, 2),
                    new MonsterData(DefaultMonsters::CREEPER, 7),
                    new MonsterData(DefaultMonsters::ENDERMAN, 2)
                )
            ),
            10 => new WaveData( #todo: boss
                $defaultTitleFormat,
                new CustomWaveStart(function (WaveController $wc) {
                    $wc->getGame()->broadcastMessage("§l§cエンダーマンの群れがレーン §e1 §cに接近中です！！");
                }),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 12),
                    new MonsterData(DefaultMonsters::ATTACKER, 4),
                    new MonsterData(DefaultMonsters::HUSK, 6),
                    new MonsterData(DefaultMonsters::SPIDER, 3),
                    new MonsterData(DefaultMonsters::PIGLIN, 2),
                    new MonsterData(DefaultMonsters::SKELETON, 1), #NEW: SKELETON,
                    new MonsterData(DefaultMonsters::ENDERMAN, 8)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 8),
                    new MonsterData(DefaultMonsters::ATTACKER, 4),
                    new MonsterData(DefaultMonsters::SPIDER, 3),
                    new MonsterData(DefaultMonsters::HUSK, 2),
                    new MonsterData(DefaultMonsters::SKELETON, 1),
                    new MonsterData(DefaultMonsters::PIGLIN, 2),
                    new MonsterData(DefaultMonsters::ENDERMAN, 2),
                    new MonsterData(DefaultMonsters::PIGLIN_BRUTE, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 6),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::SPIDER, 2),
                    new MonsterData(DefaultMonsters::CREEPER, 3),
                    new MonsterData(DefaultMonsters::PIGLIN, 3),
                    new MonsterData(DefaultMonsters::ENDERMAN, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 3),
                    new MonsterData(DefaultMonsters::ATTACKER, 2),
                    new MonsterData(DefaultMonsters::CREEPER, 7),
                    new MonsterData(DefaultMonsters::SKELETON, 1),
                    new MonsterData(DefaultMonsters::PIGLIN, 2)
                )
            ),
            11 => new WaveData(
                $defaultTitleFormat,
                new CustomWaveStart(function (WaveController $wc) {
                    $wc->getGame()->broadcastMessage("§l§cハスクとクモの群れがレーン §e1 §cに接近中です！！");
                }),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::HUSK, 18),
                    new MonsterData(DefaultMonsters::SPIDER, 9)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 8),
                    new MonsterData(DefaultMonsters::ATTACKER, 7),
                    new MonsterData(DefaultMonsters::CREEPER, 6),
                    new MonsterData(DefaultMonsters::SPIDER, 3),
                    new MonsterData(DefaultMonsters::SKELETON, 2),
                    new MonsterData(DefaultMonsters::PIGLIN, 3),
                    new MonsterData(DefaultMonsters::ENDERMAN, 2)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 4),
                    new MonsterData(DefaultMonsters::ATTACKER, 1),
                    new MonsterData(DefaultMonsters::SPIDER, 1),
                    new MonsterData(DefaultMonsters::CREEPER, 2),
                    new MonsterData(DefaultMonsters::SKELETON, 1),
                    new MonsterData(DefaultMonsters::PIGLIN, 3)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 2),
                    new MonsterData(DefaultMonsters::ATTACKER, 3),
                    new MonsterData(DefaultMonsters::CREEPER, 3),
                    new MonsterData(DefaultMonsters::SKELETON, 3),
                    new MonsterData(DefaultMonsters::PIGLIN, 3),
                    new MonsterData(DefaultMonsters::ENDERMAN, 3)
                )
            ),
            12 => new WaveData(
                $defaultTitleFormat,
                new CustomWaveStart(function (WaveController $wc) {
                    $wc->getGame()->broadcastMessage("§l§cアタッカーとクリーパーの群れがレーン §e2 §cに接近中です！");
                    $wc->getGame()->broadcastMessage("§l§cゾンビとピグリンの群れがレーン §e4 §cに接近中です！");
                }),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::HUSK, 6),
                    new MonsterData(DefaultMonsters::SPIDER, 3),
                    new MonsterData(DefaultMonsters::SKELETON, 7)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 2),
                    new MonsterData(DefaultMonsters::ATTACKER, 10),
                    new MonsterData(DefaultMonsters::DEFENDER, 3), #NEW: DEFENDER
                    new MonsterData(DefaultMonsters::CREEPER, 34),
                    new MonsterData(DefaultMonsters::SPIDER, 3),
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 1),
                    new MonsterData(DefaultMonsters::SPIDER, 1),
                    new MonsterData(DefaultMonsters::CREEPER, 2),
                    new MonsterData(DefaultMonsters::SKELETON, 1)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::ZOMBIE, 20),
                    new MonsterData(DefaultMonsters::ATTACKER, 4),
                    new MonsterData(DefaultMonsters::DEFENDER, 1),
                    new MonsterData(DefaultMonsters::CREEPER, 3),
                    new MonsterData(DefaultMonsters::PIGLIN, 12)
                )
            ),
            13 => new WaveData(
                $defaultTitleFormat,
                new CustomWaveStart(function (WaveController $wc) {
                    $wc->getGame()->broadcastMessage("§l§9クリーパーとエンダーマンの群れが接近中です！");
                }),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::CREEPER, 24),
                    new MonsterData(DefaultMonsters::ATTACKER, 5),
                    new MonsterData(DefaultMonsters::ENDERMAN, 10)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::CREEPER, 24),
                    new MonsterData(DefaultMonsters::ATTACKER, 5),
                    new MonsterData(DefaultMonsters::ENDERMAN, 10)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::CREEPER, 24),
                    new MonsterData(DefaultMonsters::ATTACKER, 5)
                ),
                new WaveMonsters(
                    new MonsterData(DefaultMonsters::CREEPER, 24),
                    new MonsterData(DefaultMonsters::ATTACKER, 5),
                )
            ),
        ]);
    }

    public function getBossBar(): BossBar {
        return $this->bossBar;
    }

    public function getShop(): Shop {
        return $this->shop;
    }

    public function getVillagerHealth() {
        return $this->villager->getHealth();
    }

    public function getVillager(): ?Villager {
        return $this->villager;
    }

    public function setVillagerHealth(float $health) {
        if ($health > $this->villager->getMaxHealth()) {
            $this->villager->setMaxHealth((int) ceil($health));
        }
        $this->villager->setHealth($health);
    }

    public function getStatus() {
        return $this->status;
    }

    public function isClosed() {
        return $this->closed;
    }

    public function getPlayers() {
        return $this->players;
    }

    public function getWaveController(): WaveController {
        return $this->waveController;
    }

    public function hasMinPlayer() {
        return count($this->getPlayers()) >= 1;
    }

    public function getCenterPosition() {
        return $this->centerPos;
    }

    public function getMaxPlayers() {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers) {
        $this->maxPlayers = $maxPlayers;
    }

    public function canJoin(?Player $player) { #player引数を設定しているのはpartyゲームや追放機能追加のため
        return !$this->closed && $this->status === self::STATUS_IDLE && count($this->getPlayers()) < $this->getMaxPlayers();
    }

    public function broadcastMessage(string|Translatable $message) {
        foreach ($this->getWorld()->getPlayers() as $player) {
            $player->sendMessage($message);
        }
    }

    public function broadcastActionBarMessage(string $message) {
        foreach ($this->getWorld()->getPlayers() as $player) {
            $player->sendActionBarMessage($message);
        }
    }

    public function broadcastTitle(string $title, string $subtitle = "") {
        foreach ($this->getWorld()->getPlayers() as $player) {
            $player->sendTitle($title, $subtitle);
        }
    }

    public function broadcastTip(string $message) {
        foreach ($this->getWorld()->getPlayers() as $player) {
            $player->sendTip($message);
        }
    }

    public function onPlayerJoin(Player $player) {
        if ($player->getWorld() === $this->world) {
            $this->log("§a{$player->getName()} has joined the game!");
            $this->broadcastMessage("§a{$player->getName()} がゲームに参加しました！");

            $this->players[spl_object_hash($player)] = $player;
        }
    }

    public function onPlayerLeave(Player $player) {
        if ($player->getWorld() !== $this->world) {
            $this->log("§c{$player->getName()} has left the game");
            $this->broadcastMessage("§c{$player->getName()} がゲームから去りました");

            if ($this->bossBar->isShowed($player)) {
                $this->bossBar->hideFromPlayer($player);
            }

            unset($this->players[spl_object_hash($player)]);
        }

        if (count($this->getPlayers()) <= 0 && !$this->canJoin(null) && !$this->closed) {
            $this->end(1 * 20);
        }
    }

    public function finishedPrepare(): void {
        if ($this->status === self::STATUS_PREPARE) {
            $this->log("§dGame Created!");
            $this->log("§dStage: {$this->stageInfo->getName()}");

            $this->cooltimeHandler->start(20 * 20);
            $this->status = self::STATUS_IDLE;
        }
    }

    public function getStageInfo(): StageInfo {
        return $this->stageInfo;
    }

    public function getWorld(): World {
        return $this->world;
    }

    public function closeEntities() {
        foreach ($this->world->getEntities() as $entity) {
            if (!($entity instanceof Player)) {
                $entity->close();
            }
        }
    }

    public function gameclear(): void {
        foreach ($this->world->getPlayers() as $player) {
            PlayerUtil::playSound($player, "random.totem", volume: 0.5);
            PlayerUtil::reset($player);
            $player->sendTitle("§eGame Clear", "§7あなたは英雄です！");
            GenericConfigAdapter::fetch($player)?->addInt(GenericConfigAdapter::GAME_WON, 1);
            GenericConfigAdapter::fetch($player)?->addInt(GenericConfigAdapter::PLAY_COUNT, 1);
            JobConfigAdapter::fetchCurrent($player)?->addInt(JobConfigAdapter::GAME_WON, 1);
            JobConfigAdapter::fetchCurrent($player)?->addInt(JobConfigAdapter::PLAY_COUNT, 1);
        }

        $this->end(11 * 20);
    }

    public function gameover(): void {
        $step = 0.8;
        $pos = $this->getCenterPosition();
        $std = new \stdClass;
        $std->size = $step;
        TaskUtil::repeatingClosureLimit(function () use ($step, $std, $pos) {
            $std->size += $step;
            $min = ($std->size - $step);
            $max = ($std->size + $step);
            foreach (EntityUtil::getWithin($pos, $min, $max) as $entity) {
                if (!$entity instanceof Player) {
                    $source = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_SUICIDE, 100000);
                    $entity->attack($source);
                } else {
                    $angle = VectorUtil::getAngle($pos, $entity->getPosition());
                    $dir = VectorUtil::getDirectionHorizontal($angle->x);

                    $entity->setMotion($dir->add(0, 0.4, 0));
                }
            }

            ParticleUtil::send((new SphereParticle($std->size, 8, 8, 360, -90, 0)), $this->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("starpve:freeze_gas", ""));

            foreach ($this->getWorld()->getPlayers() as $player) {
                $anchor = VectorUtil::getNearestSpherePosition($player->getPosition(), $pos, $std->size);
                $dist = $anchor->distance($player->getPosition());
                #(new SingleParticle())->sendToPlayer($player, VectorUtil::insertWorld($anchor, $player->getWorld()), "minecraft:balloon_gas_particle");
                #$player->sendMessage("dist: {$dist} anchor: {$anchor} player: {$player->getPosition()->asVector3()}");
                $volume = max(0, 1.0 - ($dist / 12));
                PlayerUtil::playSound($player, "block.false_permissions", 0.2, $volume);
            }
        }, 2, 38);
        TaskUtil::delayed(new ClosureTask(function () {
            foreach ($this->getPlayers() as $player) {
                PlayerUtil::reset($player);
                PlayerUtil::playSound($player, "mob.evocation_illager.prepare_wololo", 1.0, 1.0);
                GenericConfigAdapter::fetch($player)?->addInt(GenericConfigAdapter::GAME_LOST, 1);
                GenericConfigAdapter::fetch($player)?->addInt(GenericConfigAdapter::PLAY_COUNT, 1);
                JobConfigAdapter::fetchCurrent($player)?->addInt(JobConfigAdapter::GAME_LOST, 1);
                JobConfigAdapter::fetchCurrent($player)?->addInt(JobConfigAdapter::PLAY_COUNT, 1);
            }

            $this->end(15 * 20);
        }), 10 + (2 * 38));

        $this->log("§6Game Over...");
    }

    public function end(int $closeDelay) {
        $this->status = self::STATUS_ENDING;

        $this->stageInfo->getIdentityGroup()->reset();
        $this->stageInfo->getIdentityGroup()->close();

        $this->broadcastMessage("§6>> §eモンスターキル数");
        $this->broadcastMessage($this->waveController->getKillCounter()->createRanking());
        $this->broadcastMessage("§6>> §eダメージ");
        $this->broadcastMessage($this->waveController->getDamageCounter()->createRanking());

        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () {
            $this->closeEntities();
        }), 5);

        $this->log("§7Closing the game...");
        $this->waveController->demonKill();

        $this->breakCooltimeHandler();

        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () {
            $this->close();
        }), max(6, $closeDelay));
    }

    protected function close() {
        $this->status = self::STATUS_IDLE;
        $this->closed = true;

        $this->bossBar->hide();

        $gameManager = StarPvE::getInstance()->getGameManager();
        foreach ($this->world->getPlayers() as $player) {
            $gamePlayer = $this->getGamePlayer($player);
            $gamePlayer?->leaveGame();
        }
        $gameManager->cleanGame($this->world->getFolderName());
        $this->log("§dSuccessfly Closed");
    }

    protected function getGamePlayer(Player $player) {
        return StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($player);
    }

    public function giveEquipments(Player $player): void {
        $this->getGamePlayer($player)?->refreshEquipment();
        PlayerUtil::give($player, ItemFactory::getInstance()->get(ItemIds::BOOK, 0, 1));
        PlayerUtil::give($player, ItemFactory::getInstance()->get(ItemIds::BREAD, 0, 12));
    }

    public function start(): void {
        $this->status = self::STATUS_STARTING;
        $this->log("Starting Game...");

        $this->bossBar->showToWorld($this->world);

        $this->cooltimeHandler = new CooltimeHandler("Game Start Tick", CooltimeHandler::BASE_SECOND, 1);
        $this->cooltimeHandler->attach($this);
        $this->cooltimeHandler->start(10 * 20);

        $this->closeEntities();

        foreach ($this->getPlayers() as $player) {
            $player->sendTitle("ゲームが開始されます");
            $this->giveEquipments($player);
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


        $ev = new GameStartEvent($this);
        $ev->call();
    }

    protected function onStarted(): void {
        $this->status = self::STATUS_PLAYING;

        $this->log("§6Game Started!");
        $this->waveController->waveStart();
    }

    public function log(string $message) {
        $id = $this->world->getFolderName();
        StarPvE::getInstance()->log("§7[Game - {$id}] §7{$message}");
    }

    public function cooltimeTick(CooltimeHandler $cooltimeHandler, int $remain): bool {
        if ($cooltimeHandler->getId() === "Game Tick") {
            if ($this->hasMinPlayer()) {
                if ($cooltimeHandler->getRemain() === $cooltimeHandler->getTime()) {
                    $this->log("§7Players Ready!");
                }

                foreach ($this->world->getPlayers() as $player) {
                    if ($cooltimeHandler->getRemain() === $cooltimeHandler->getTime()) {
                        PlayerUtil::playSound($player, "random.click", 0.75, 1.0);
                    }
                    $player->sendActionBarMessage("人数が揃いました！ 準備しています... (残り {$remain}秒 で開始)");
                }
                return true;
            } else {
                if ($cooltimeHandler->getRemain() < $cooltimeHandler->getTime()) {
                    $this->broadcastActionBarMessage("キャンセルされました");
                }
                $cooltimeHandler->reset();
            }
        } elseif ($cooltimeHandler->getId() === "Game Start Tick") {
            foreach ($this->world->getPlayers() as $player) {
                $player->sendTitle("§r ", "§c- {$remain} -");
                PlayerUtil::playSound($player, "note.bd", volume: 0.5); #名前指定引数！！いひーｗｗ
            }
            return true;
        }

        return false;
    }


    public function cooltimeFinished(CooltimeHandler $cooltimeHandler): void {
        $this->breakCooltimeHandler();
        if ($cooltimeHandler->getId() === "Game Tick") {
            $this->start();
        } elseif ($cooltimeHandler->getId() === "Game Start Tick") {
            $this->onStarted();
        }
    }
}
