<?php

use Lyrica0954\MagicParticle\CoveredParticle;
use Lyrica0954\MagicParticle\PartDelayedParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use pocketmine\player\Player;

/**
 * @var Player $_player
 */

$par = new PartDelayedParticle(new CoveredParticle(new SphereParticle(5, 6, 6), $_player->getPosition()), 1, 6);

ParticleUtil::send(
	$par,
	$_player->getWorld()->getPlayers(),
	null,
	ParticleOption::spawnPacket("starpve:soft_red_gas")
);
