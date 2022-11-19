<?php

use Lyrica0954\StarPvE\job\player\archer\ArrowPartySkill;
use Lyrica0954\StarPvE\job\player\archer\SpecialBowAbility;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\player\Player;

/**
 * @var Player $_player
 */

$job = StarPvE::getInstance()->getJobManager()->getJob($_player);

if ($job !== null) {
	$ability = $job->getSkill();
	$ability->getCooltime()->multiply(0.1);
}
