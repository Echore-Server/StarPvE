<?php


declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity;

use Lyrica0954\StarPvE\identity\player\PlayerArgIdentity;
use pocketmine\player\Player;

class IdentityUtil {

	public static function playerArg(Identity $identity, Player $player): Identity {
		if ($identity instanceof PlayerArgIdentity) {
			$identity->setPlayer($player);
		}

		return $identity;
	}

	public static function argAdd(IdentityGroup $identityGroup, Identity $identity, Player $player) {
		$identityGroup->add(self::playerArg($identity, $player));
	}
}
