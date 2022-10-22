<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\ranking;

use Closure;
use Lyrica0954\Ranking\Ranking;
use Lyrica0954\Ranking\RankingManager;
use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

class Rankings {

	public static function generic(PlayerDataCenter $dataCenter, Vector3 $pos, string $format, string $title, string $key): RankingEntry {
		return self::normal($dataCenter, $pos, $format, $title, self::genericConsumer($dataCenter, $key));
	}

	public static function normal(PlayerDataCenter $dataCenter, Vector3 $pos, string $format, string $title, Closure $updateConsumer): RankingEntry {
		return new RankingEntry(
			new RankingManager(),
			$title,
			$pos,
			$format,
			10,
			$updateConsumer
		);
	}

	public static function genericConsumer(PlayerDataCenter $dataCenter, string $key): Closure {
		return self::configConsumer($dataCenter, "generic", $key);
	}

	public static function configConsumer(PlayerDataCenter $dataCenter, string $content, string $key): Closure {
		return self::calculationConfigConsumer($dataCenter, $content, function (PlayerConfigAdapter $adapter) use ($key): float {
			return $adapter->getConfig()->get($key, 0.0);
		});
	}

	public static function calculationConfigConsumer(PlayerDataCenter $dataCenter, string $content, Closure $valueCalculator): Closure {
		$content = "get" . $content;
		Utils::validateCallableSignature(function (PlayerConfigAdapter $adapter): float {
			return 0;
		}, $valueCalculator);
		return function (array $list, RankingManager $manager) use ($dataCenter, $valueCalculator, $content): void {
			foreach ($dataCenter->getAll() as $xuid => $config) {
				$generic = $config->$content();
				$value = $valueCalculator($generic);
				$name = $generic->getConfig()->get(GenericConfigAdapter::USERNAME);
				$ranking = $list[$name] ?? (new Ranking($name));
				$ranking->setValue($value);
				$manager->register($ranking);
			}
		};
	}
}
