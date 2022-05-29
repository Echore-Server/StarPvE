<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\stage;

use Lyrica0954\StarPvE\game\identity\AmpMonsterHealthArgIdentity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use pocketmine\math\Vector3;
use pocketmine\utils\SingletonTrait;

class StageFactory {
	use SingletonTrait {
		getInstance as Singleton_getInstance;
	}

	/**
	 * @var StageInfo[]
	 */
	private array $list = [];

	public function __construct() {
		$this->register(
			new StageInfo(
				DefaultStages::CASTLE,
				new Vector3(-49.5, 48.6, -49.5),
				new Vector3(-49.5, 48, -21.5),
				new Vector3(-77.5, 48, -49.5),
				new Vector3(-49.5, 48, -77.5),
				new Vector3(-21.5, 48, -49.5),
				"map",
				"Lyrica0954",
				new IdentityGroup
			)
		);

		$this->register(
			new StageInfo(
				DefaultStages::LABORATORY,
				new Vector3(1.5, 55.6, 1.5),
				new Vector3(-21.5, 55, 1.5),
				new Vector3(1.5, 55, -21.5),
				new Vector3(23.5, 55, 1.5),
				new Vector3(1.5, 55, 23.5),
				"map_1",
				"Lyrica0954",
				new IdentityGroup
			)
		);

		$ident = new IdentityGroup;
		$list = [
			new AmpMonsterHealthArgIdentity(1.2)
		];

		$ident->addAll($list);

		$this->register(
			new StageInfo(
				DefaultStages::STAIR,
				new Vector3(0.5, 101.6, 0.5),
				new Vector3(37.5, 81, 1.5),
				new Vector3(37.5, 81, -0.5),
				new Vector3(39.5, 81, -0.5),
				new Vector3(39.5, 81, 1.5),
				"map_2",
				"Lyrica0954",
				$ident
			)
		);
	}

	public static function getInstance(): StageFactory {
		return self::Singleton_getInstance();
	}

	public function register(StageInfo $stageInfo, bool $override = false) {
		if (!isset($this->list[$stageInfo->getName()]) || $override) {
			$this->list[$stageInfo->getName()] = clone $stageInfo;
		} else {
			throw new \Exception("cannot override");
		}
	}

	public function get(string $name): ?StageInfo {
		$stageInfo = $this->list[$name] ?? null;

		if ($stageInfo instanceof StageInfo) {
			$stageInfo = clone $stageInfo;
		}

		return $stageInfo;
	}

	/**
	 * @return StageInfo[]
	 */
	public function getList(): array {
		return $this->list;
	}
}
