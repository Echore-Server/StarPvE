<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class GamePlayer {

    private Player $player;
    private ?Game $game;

    public int $armorLevel;
    public int $swordLevel;

    const SWORD_PRICE = [
        0 => 40,
        1 => 90,
        2 => 160
    ];

    const ARMOR_PRICE = [
        0 => 40,
        1 => 90,
        2 => 160
    ];

    public function __construct(Player $player){
        $this->player = $player;
        $this->game = null;

        $this->resetStuff();
    }

    public function getArmorMax(){
        return max(array_keys(self::ARMOR_PRICE));
    }

    public function getSwordMax(){
        return max(array_keys(self::SWORD_PRICE));
    }

    public function isArmorMax(){
        return $this->getArmorMax() <= $this->armorLevel;
    }

    
    public function isSwordMax(){
        return $this->getSwordMax() <= $this->swordLevel;
    }

    public function updatePrice(){
        $this->armorPrice = self::ARMOR_PRICE[$this->armorLevel] ?? 0;
        $this->swordPrice = self::SWORD_PRICE[$this->swordLevel] ?? 0;
    }

    public function resetStuff(){
        $this->armorLevel = 0;
        $this->swordLevel = 0;
        $this->updatePrice();
    }

    public function updateStuff(){
        $this->equipArmor($this->armorLevel);
        $this->equipSword($this->swordLevel);
    }

    public function armorLevelup(){
        if (!$this->isArmorMax()){
            $this->armorLevel ++;
            $this->equipArmor($this->armorLevel);
            $this->updatePrice();
        }
    }

    public function equipArmor(int $level){
        $armorSet = match($level){
            0 => ArmorSet::leather(),
            1 => ArmorSet::iron(),
            2 => ArmorSet::diamond()
        };

        $armorSet->setUnbreakable();

        $armorSet->equip($this->player);
    }

    public function swordLevelup(){
        if (!$this->isSwordMax()){
            $this->swordLevel ++;
            $this->equipSword($this->swordLevel);
            $this->updatePrice();
        }
    }

    public function equipSword(int $level){
        $sword = match($level){
            0 => ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD),
            1 => ItemFactory::getInstance()->get(ItemIds::IRON_SWORD),
            2 => ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD)
        };

        $sword->setUnbreakable();

        $index = PlayerUtil::findSwordIndex($this->player);
        if ($index === null){
            $this->player->getInventory()->addItem($sword);
        } else {
            $this->player->getInventory()->setItem($index, $sword);
        }
    }

    public function getPlayer(){
        return $this->player;
    }

    public function getGame(){
        return $this->game;
    }

    public function setGame(?Game $game){
        $this->game = $game;
    }

    public function joinGame(Game $game){
        $this->setGame($game);
        PlayerUtil::reset($this->player);
        $this->player->teleport($game->getCenterPosition());
        $game->onPlayerJoin($this->player);
        
    }

    public function leaveGame(){
        PlayerUtil::reset($this->player);
        PlayerUtil::teleportToLobby($this->player);
        if ($this->game instanceof Game){
            $this->game->onPlayerLeave($this->player);
        }
        $this->resetStuff();
        $this->setGame(null);
    }

    public function setGameFromId(?string $id){
        if ($id === null){
            $this->setGame(null);
        } else {
            $gameManager = StarPvE::getInstance()->getGameManager();
            if (($game = $gameManager->getGame($id)) !== null){
                $this->setGame($game);
            }
        }
    }

}