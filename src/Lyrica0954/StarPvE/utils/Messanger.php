<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use Lyrica0954\StarPvE\data\condition\Condition;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Messanger {

    public static function talk(Player $player, string $by, string $message) {
        $player->sendMessage("§d{$by} §7>> §f{$message}");
    }

    public static function reward(Player $player, string $content, string $amount = null, string $color = "§7") {
        $amount = ($amount !== null) ? $amount : "";

        $player->sendMessage("§a+ {$color}{$content} [{$amount}]");
    }

    public static function normalReward(Player $player, string $content, string $amount = null) {
        self::reward($player, $content, $amount, "§7");
    }

    public static function rareReward(Player $player, string $content, string $amount = null) {
        self::reward($player, $content, $amount, "§b");
    }

    public static function error(CommandSender $sender, string $message, string $id) {
        $sender->sendMessage("§c<!> {$message} §7(ID: {$id})");
    }

    public static function getIdFromObject(object $object, string $function, string $add = "") {
        $ref = new \ReflectionClass($object);
        $name = $ref->getShortName();
        $id = "{$name}#{$function}<F>-{$add}<C>";
        return $id;
    }

    public static function condition(Player $player, ?Condition $condition) {
        $text = ($condition instanceof Condition) ? ($condition->asText()) : "なし";
        $player->sendMessage("§c--------------\n§7{$text}\n§c--------------");
    }

    public static function tooltip(Player $player, string $message) {
        $player->sendMessage($message);
    }
}
