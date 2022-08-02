<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\StarPvE\game\identity\AmpMonsterHealthArgIdentity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\utils\ArmorSet;
use pocketmine\item\ArmorTypeInfo;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\utils\SingletonTrait;

class MonsterFactory {
	use SingletonTrait {
		getInstance as Singleton_getInstance;
	}

	/**
	 * @var MonsterOption[]
	 */
	private array $list = [];

	public function __construct() {

		$this->register(
			new MonsterOption(
				DefaultMonsters::ZOMBIE,
				new MonsterAttribute(20, 4.5, 0.35),
				new ArmorSet(
					VanillaItems::IRON_HELMET(),
					VanillaItems::LEATHER_TUNIC(),
					null,
					null
				),
				[
					VanillaItems::EMERALD()->setCount(1)
				],
				1
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::ATTACKER,
				new MonsterAttribute(100, 6.0, 0.031),
				ArmorSet::none(),
				[
					VanillaItems::EMERALD()->setCount(4)
				],
				3
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::CREEPER,
				new MonsterAttribute(10, 1.0, 0.45),
				ArmorSet::none(),
				[
					VanillaItems::EMERALD()->setCount(1)
				],
				2
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::SPIDER,
				new MonsterAttribute(28, 3.0, 0.37),
				ArmorSet::none(),
				[
					VanillaItems::EMERALD()->setCount(1)
				],
				1
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::HUSK,
				new MonsterAttribute(24, 8.0, 0.2),
				new ArmorSet(
					VanillaItems::DIAMOND_HELMET(),
					VanillaItems::CHAINMAIL_CHESTPLATE(),
					VanillaItems::CHAINMAIL_LEGGINGS(),
					VanillaItems::CHAINMAIL_BOOTS()
				),
				[
					VanillaItems::EMERALD()->setCount(2),
					VanillaItems::BREAD()->setCount(1)
				],
				1
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::SKELETON,
				new MonsterAttribute(42, 2.0, 0.21),
				new ArmorSet(
					VanillaItems::LEATHER_CAP(),
					VanillaItems::DIAMOND_CHESTPLATE(),
					VanillaItems::DIAMOND_LEGGINGS(),
					VanillaItems::LEATHER_BOOTS()
				),
				[
					VanillaItems::EMERALD()->setCount(2)
				],
				4
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::DEFENDER,
				new MonsterAttribute(70, 0.5, 0.3),
				ArmorSet::none(),
				[
					VanillaItems::EMERALD()->setCount(5)
				],
				6
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::PIGLIN,
				new MonsterAttribute(22, 4.5, 0.6),
				new ArmorSet(
					VanillaItems::GOLDEN_HELMET(),
					VanillaItems::GOLDEN_CHESTPLATE(),
					VanillaItems::GOLDEN_LEGGINGS(),
					VanillaItems::GOLDEN_BOOTS(),
					VanillaItems::GOLDEN_SWORD()
				),
				[
					VanillaItems::EMERALD()->setCount(2)
				],
				3
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::ENDERMAN,
				new MonsterAttribute(22, 4, 0.4),
				ArmorSet::none(),
				[
					VanillaItems::EMERALD()->setCount(6)
				],
				8
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::PIGLIN_BRUTE,
				new MonsterAttribute(250, 30, 0.24),
				ArmorSet::none(),
				[
					VanillaItems::EMERALD()->setCount(20)
				],
				80
			)
		);


		$this->register(
			new MonsterOption(
				DefaultMonsters::ZOMBIE_LORD,
				new MonsterAttribute(360, 10.0, 0.22),
				ArmorSet::chainmail(),
				[
					VanillaItems::EMERALD()->setCount(10)
				],
				20
			)
		);

		$this->register(
			new MonsterOption(
				DefaultMonsters::STRAY,
				new MonsterAttribute(270, 3.0, 0.24),
				ArmorSet::iron(),
				[
					VanillaItems::BREAD()->setCount(6)
				],
				50
			)
		);
	}

	public static function getInstance(): MonsterFactory {
		return self::Singleton_getInstance();
	}

	public function register(MonsterOption $monsterOption, bool $override = false) {
		if (!isset($this->list[$monsterOption->getClass()]) || $override) {
			$this->list[$monsterOption->getClass()] = clone $monsterOption;
		} else {
			throw new \Exception("cannot override");
		}
	}

	public function get(string $class): ?MonsterOption {
		$monsterOption = $this->list[$class] ?? null;

		if ($monsterOption instanceof MonsterOption) {
			$monsterOption = clone $monsterOption;
		}

		return $monsterOption;
	}

	/**
	 * @return MonsterOption[]
	 */
	public function getList(): array {
		return $this->list;
	}
}
