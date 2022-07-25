<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use Lyrica0954\MagicParticle\effect\PartDelayedEffect;
use Lyrica0954\MagicParticle\effect\ParticleEffect;
use Lyrica0954\MagicParticle\PartDelayedParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SendableParticle;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\world\Position;

class ParticleUtil {

    public static function send(PartDelayedParticle|PartDelayedEffect|SendableParticle|ParticleEffect $particle, array $players, ?Position $pos = null, ParticleOption $option) {
        $sender = StarPvE::getInstance()->getParticleHost()->getSender();

        if (!$particle instanceof PartDelayedParticle && $pos === null) {
            throw new \Exception("pos null not allowed");
        }

        if ($particle instanceof ParticleEffect) { #PartDelayedEffect, ParticleEffect
            $sender->sendEffect($particle, $players, $pos, $option);
        } elseif ($particle instanceof PartDelayedParticle) {
            $sender->sendPartDelayed($particle, $players, $option);
        } elseif ($particle instanceof SendableParticle) {
            $sender->sendParticle($particle, $players, $pos, $option);
        }
    }
}
