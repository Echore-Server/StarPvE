<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

use Lyrica0954\MagicParticle\effect\PartDelayedEffect;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\StarPvE\form\JobSelectForm;
use pocketmine\entity\Human;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

use Lyrica0954\StarPvE\PlayerController;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EmoteIds;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class JobShop extends Human implements Ghost{
    
    protected $lookTick = 0;
    protected $emoted = array();

    public function getName(): String{
        return "JobShop";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(1.8, 0.6);
    }

    public static function getNetworkTypeId(): string{
        return EntityIds::PLAYER;
    }
    
    public function onInteract(Player $player, Vector3 $clickPos): bool{
        $jobSelect = new JobSelectForm($player);
        $player->sendForm($jobSelect);
        return true;
    }

    public function entityBaseTick(int $tickDiff = 1): bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->lookTick += $tickDiff;
        if ($this->lookTick >= 6){
            $this->lookTick = 0;

            $ef = new PartDelayedEffect((new SaturatedLineworkEffect(16, 3, 1, 7)), 2, 1, true);
            $ef->sendToPlayers($this->getWorld()->getPlayers(), VectorUtil::keepAdd($this->getPosition(), 0, $this->getEyeHeight(), 0), "minecraft:balloon_gas_particle");

            $nearestDist = PHP_INT_MAX;
            $nearestPlayer = null;
            foreach($this->getWorld()->getPlayers() as $player){
                $dist = $this->getPosition()->distance($player->getPosition());
                if ($dist < $nearestDist){
                    $nearestDist = $dist;
                    $nearestPlayer = $player;
                }

                $gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
                if (!in_array($player, $this->emoted, true) && ($gamePlayerManager->getGamePlayer($player) !== null)){
                    if ($dist <= 5.0){
                        $this->lookAt($player->getEyePos());
                        $packet = EmotePacket::create($this->getId(), EmoteIds::WAVE, 1<<0);
                        $player->getNetworkSession()->sendDataPacket($packet);
                        
                        $this->emoted[] = $player;
                    }
                }
            }
            
            if ($nearestPlayer !== null){
                $this->lookAt($nearestPlayer->getEyePos());
            }
        }

        return $hasUpdate;
    }
}