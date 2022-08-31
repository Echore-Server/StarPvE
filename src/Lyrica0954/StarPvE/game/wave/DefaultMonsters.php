<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\monster\boss\GiantAttacker;
use Lyrica0954\StarPvE\game\monster\boss\Stray;
use Lyrica0954\StarPvE\game\monster\boss\ZombieLord;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\monster\Defender;
use Lyrica0954\StarPvE\game\monster\Enderman;
use Lyrica0954\StarPvE\game\monster\Husk;
use Lyrica0954\StarPvE\game\monster\MagePiglin;
use Lyrica0954\StarPvE\game\monster\Piglin;
use Lyrica0954\StarPvE\game\monster\PiglinBrute;
use Lyrica0954\StarPvE\game\monster\Skeleton;
use Lyrica0954\StarPvE\game\monster\Spider;
use Lyrica0954\StarPvE\game\monster\Zombie;

class DefaultMonsters {

	const ZOMBIE = Zombie::class;
	const CREEPER = Creeper::class;
	const ATTACKER = Attacker::class;
	const SPIDER = Spider::class;
	const HUSK = Husk::class;
	const SKELETON = Skeleton::class;
	const DEFENDER = Defender::class;
	const PIGLIN = Piglin::class;
	const ENDERMAN = Enderman::class;
	const PIGLIN_BRUTE = PiglinBrute::class;
	const MAGE_PIGLIN = MagePiglin::class;

	const ZOMBIE_LORD = ZombieLord::class;
	const STRAY = Stray::class;
	const GIANT_ATTACKER = GiantAttacker::class;

	public static function getDefaults(): array {
		$ref = new \ReflectionClass(self::class);
		$classes = $ref->getConstants();
		return array_values($classes);
	}
}
