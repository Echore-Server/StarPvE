<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\translate;

use pocketmine\lang\Translatable;
use pocketmine\Server;

class SimpleTranslator {

	public static function translate(Translatable $text): string{
		$lang = Server::getInstance()->getLanguage();
		return $lang->translate($text);
	}
}