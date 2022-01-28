<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\player\Player;

class Messanger {

    public static function talk(Player $player, string $by, string $message){
        $player->sendMessage("§d{$by} §7>> §f{$message}");
    }

    public static function reward(Player $player, string $content, string $amount = null, string $color = "§7"){
        $amount = ($amount !== null) ? $amount : "";

        $player->sendMessage("§a+ {$color}{$content} [{$amount}]");
    }

    public static function normalReward(Player $player, string $content, string $amount = null){
        self::reward($player, $content, $amount, "§7");
    }

    public static function rareReward(Player $player, string $content, string $amount = null){
        self::reward($player, $content, $amount, "§b");
    }

    
}