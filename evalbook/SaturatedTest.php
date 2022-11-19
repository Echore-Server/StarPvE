<?php

use Lyrica0954\MagicParticle\effect\PartDelayedEffect;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use pocketmine\player\Player;

/**
 * @var Player $_player
 */

$par = new PartDelayedEffect(new SaturatedLineworkEffect(20, 3, 2, 80), 1, 1, true);

ParticleUtil::send($par, $_player->getWorld()->getPlayers(), $_player->getPosition(), ParticleOption::spawnPacket("starpve:soft_red_gas"));
