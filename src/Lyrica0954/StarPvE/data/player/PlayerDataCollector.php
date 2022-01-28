<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use pocketmine\player\Player;

class PlayerDataCollector {

    public static function setGenericConfig(Player $player, string $key, mixed $value){
        PlayerDataCenter::getInstance()?->get($player)?->getGeneric()->set($key, $value);
    }

    public static function setJobConfig(Player $player, string $key, mixed $value){
        PlayerDataCenter::getInstance()?->get($player)?->getJob()->set($key, $value);
    }

    public static function getGenericConfig(Player $player, string $key): mixed{
        return PlayerDataCenter::getInstance()?->get($player)?->getGeneric()->get($key);
    }

    public static function getJobConfig(Player $player, string $key): mixed{
        return PlayerDataCenter::getInstance()?->get($player)?->getJob()->get($key);
    }

    public static function addGenericDigit(Player $player, string $key, float $add): mixed{
        $new = self::getGenericConfig($player, $key);
        if (is_int($new) || is_float($new)){ #phpはそんなに厳しくないからis_floatだけでintegerもついてくる説
            $new += $add;

            self::setGenericConfig($player, $key, $new);
            return self::getGenericConfig($player, $key);
        } else {
            throw new \Exception("expected int/float");
        }
    }

    public static function addJobDigit(Player $player, string $key, float $add): mixed{
        $new = self::getJobConfig($player, $key);
        if (is_int($new) || is_float($new)){ #phpはそんなに厳しくないからis_floatだけでintegerもついてくる説
            $new += $add;

            self::setJobConfig($player, $key, $new);
            return self::getJobConfig($player, $key);
        } else {
            throw new \Exception("expected int/float");
        }
    }

    public static function addExp(Player $player, float $amount): mixed{
        $exp = self::addGenericDigit($player, "Exp", $amount);
        self::addGenericDigit($player, "TotalExp", $amount);
        $nextExp = self::getGenericConfig($player, "NextExp");
        $newExp = $exp;
        if ($exp >= $nextExp){
            $level = self::addGenericDigit($player, "Level", 1);
            $over = ($exp - $nextExp);
            self::setGenericConfig($player, "Exp", 0);
            $newNextExp = PlayerConfig::getExpToCompleteLevel((integer) $level);
            self::setGenericConfig($player, "NextExp", $newNextExp);
            $newExp = 0;
            if ($over > 0) $newExp = self::addExp($player, $over);

            $previousLevel = $level - 1;
            $player->sendMessage("§a§lレベルアップ！！");
            $player->sendMessage("§a§l> {$previousLevel} => {$level}");
            $player->sendMessage("§a§l> §r§a{$newExp}§f/§a{$newNextExp}");
        }

        return $newExp;
    }
}