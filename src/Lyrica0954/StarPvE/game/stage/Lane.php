<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\stage;

use Lyrica0954\MagicParticle\ParticleOption;
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

class Lane {
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

    public function __construct(Position $start, Position $end) {
        $this->attackers = [];
        $this->lastBlocks = [];
        $this->start = $start;
        $this->end = $end;
        #$this->addRepeatingTask(new ClosureTask(function (){
        #    $this->updateLaneState();
        #}), 1);
    }

    public function getStart(): Position {
        return $this->start;
    }

    public function getEnd(): Position {
        return $this->end;
    }

    public function addAttacker(Attacker $attacker) {
        $this->attackers[spl_object_hash($attacker)] = $attacker;
    }

    public function onAttackerDeath(Attacker $dead) {
        if (isset($this->attackers[spl_object_hash($dead)])) {
            unset($this->attackers[spl_object_hash($dead)]);
        }
    }
}
