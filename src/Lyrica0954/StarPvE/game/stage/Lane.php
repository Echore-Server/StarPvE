<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\stage;

use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\task\TaskHolder;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\StainedGlass;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

class Lane{
    use TaskHolder;

    /**
     * @var Attacker[]
     */
    private array $attackers;

    public Position $start;
    public Position $end;

    /**
     * @var Block[]
     */
    private array $lastBlocks;

    public function __construct(Position $start, Position $end){
        $this->attackers = [];
        $this->lastBlocks = [];
        $this->start = $start;
        $this->end = $end;

        $this->start->y = $this->end->y;

        $this->addRepeatingTask(new ClosureTask(function (){
            $this->updateLaneState();
        }), 1);
    }

    public function getStart(): Position{
        return $this->start;
    }

    public function getEnd(): Position{
        return $this->end;
    }

    public function addAttacker(Attacker $attacker){
        $this->attackers[spl_object_hash($attacker)] = $attacker;
    }

    public function onAttackerDeath(Attacker $dead){
        if (isset($this->attackers[spl_object_hash($dead)])){
            unset($this->attackers[spl_object_hash($dead)]);
        }
    }

    public function updateLaneState(): void{
        $ne = null;
        $nd = PHP_INT_MAX;
        return; #todo
        foreach($this->attackers as $attacker){
            $dist = $attacker->getPosition()->distance($this->end);
            if ($dist < $nd){
                $nd = $dist;
                $ne = $attacker;
            }
        }

        if ($ne instanceof Attacker){
            $xds = $this->end->x - $this->start->x;
            $zds = $this->end->z - $this->start->z;
            $epos = $ne->getPosition();
            $subt = $this->end->subtractVector($epos);

            $xi = false;
            $zi = false;
            if (abs($zds) <= 0.5){
                $subt->z = 0;
                $zi = true;
            } elseif (abs($xds) <= 0.5){
                $subt->x = 0;
                $xi = true;
            }

            $ord = ($xi ? $this->end->x > $this->start->x : ($zi ? $this->end->z > $this->start->z : false));

            $l = $subt->length();
            $step = $subt->divide($l);
            $blocks = [];
            for($r = 0; $r <= $l; $r ++){
                $nPos = $epos->addVector($step->multiply($r));
                $blocks[] = $this->start->getWorld()->getBlock($nPos->subtract(0, 1, 0));
            }
            $count = count($blocks);
            for ($i = 0; $i <= $count; $i++){
                $block = $blocks[$i] ?? null;
                $bpos = $block?->getPosition();
                $previousBlock = $this->lastBlocks[$i] ?? null;
                if ($block instanceof StainedGlass){
                    $dist = ($xi ? $this->end->x - $bpos->x : ($zi ? $this->end->z - $bpos->z : 0));
                    $ok = $ord ? $dist > 0 : $dist < 0;
                    if ($ok){
                        if ($block->getMeta() != 13){
                            $newBlock = new StainedGlass(new BlockIdentifier(BlockLegacyIds::STAINED_GLASS, 13), "Stained Glass", new BlockBreakInfo(0));
                            $this->start->getWorld()->setBlock($block->getPosition(), $newBlock);
                        }
                    } else {
                        $newBlock = new StainedGlass(new BlockIdentifier(BlockLegacyIds::STAINED_GLASS, 4), "Stained Glass", new BlockBreakInfo(0));
                        $this->start->getWorld()->setBlock($block->getPosition(), $newBlock);

                        if ($previousBlock instanceof StainedGlass){
                            if ($previousBlock->getMeta() == 13){
                                $particle = new SingleParticle();
                                $particle->sendToPlayers($this->start->getWorld()->getPlayers(), $block->getPosition(), "minecraft:knockback_roar_particle");

                            }
                        }
                    }
                }

                if ($previousBlock instanceof StainedGlass){
                    
                }
            }

            $this->lastBlocks = $blocks;
        }
    }
}