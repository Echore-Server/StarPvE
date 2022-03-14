<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use Lyrica0954\StarPvE\data\DataCenter;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerDataCenter extends DataCenter implements Listener{

    private static ?PlayerDataCenter $instance = null;

    public static function getInstance(): ?PlayerDataCenter{
        return self::$instance;
    }

    private array $genericDefault;

    public function __construct(string $folder){
        self::$instance = $this;
        $this->data = [];

        $this->genericDefault = [
            "MonsterKills" => 0,
            "Deaths" => 0,
            "PlayCount" => 0,
            "GameWon" => 0,
            "GameLost" => 0,
            "Level" => 1,
            "TotalExp"=> 0,
            "Exp" => 0,
            "NextExp" => PlayerConfig::getExpToCompleteLevel(1)
        ];

        $this->load($folder);
        StarPvE::getInstance()->getServer()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
    }

    public function log(string $message){
        StarPvE::getInstance()->log("§7[PlayerDataCenter] {$message}");
    }

    public function get(Player $player): ?PlayerConfig{
        return $this->data[$player->getXuid()] ?? null;
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if (!$this->exist($player)) $this->createFor($player);
    }

    protected function load(string $folder){
        $count = 0;
        foreach(glob($folder . "/*", GLOB_ONLYDIR) as $pdFolder){
            $generic = new Config("{$pdFolder}/generic.yml", Config::YAML, $this->genericDefault);
            $job = new Config($pdFolder . "/job.yml");
            $xuid = basename($pdFolder);
            $this->data[$xuid] = new PlayerConfig($generic, $job);
            $count++;
        }

        $this->log("§6Loaded {$count} Data");
    }

    public function exist(Player $player){
        return isset($this->data[$player->getXuid()]);
    }

    public function save(){
        foreach($this->data as $config){
            if ($config instanceof PlayerConfig){
                $config->getGeneric()->save();
                $config->getJob()->save();
            }
        }
    }

    public function reload(){
        foreach($this->data as $config){
            if ($config instanceof PlayerConfig){
                $config->getGeneric()->reload();
                $config->getJob()->reload();
            }
        }
    }

    public function createGenericConfig(Player $player){
        $info = [
            "Username"=> $player->getName(),
            "FirstPlayed" => $player->getFirstPlayed(),
            "LastPlayed" => $player->getLastPlayed(),
        ];
        $default = array_merge($info, $this->genericDefault);
        $dataFolder = StarPvE::getInstance()->getDataFolder();
        $file = $dataFolder . "player_data/{$player->getXuid()}/generic.yml";
        $generic = new Config($file, Config::YAML, $default);
        return $generic;
    }

    public function createJobConfig(Player $player){
        $default = [
        ];
        $dataFolder = StarPvE::getInstance()->getDataFolder();
        $file = $dataFolder . "player_data/{$player->getXuid()}/job.yml";
        $job = new Config($file, Config::YAML, $default);
        return $job;
    }

    public function createFor(Player $player): void{
        $dataFolder = StarPvE::getInstance()->getDataFolder();
        @mkdir($dataFolder . "player_data/{$player->getXuid()}");

        $this->data[$player->getXuid()] = new PlayerConfig($this->createGenericConfig($player), $this->createJobConfig($player));
    }
}