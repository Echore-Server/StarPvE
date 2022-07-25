<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data;

use pocketmine\utils\Config;

class ConfigHelper {

	public static function addValue(Config $config, array $data): void {
		foreach ($data as $k => $v) {
			$config->set($k, $v);
		}
	}
}
