<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\particle;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\world\particle\Particle;

class ElectricSparkParticle implements Particle {

    public function encode(Vector3 $pos): array{
        return [LevelEventPacket::standardParticle(ParticleIds::ELECTRIC_SPARK, 1, $pos)];
    }
}