<?php

declare(strict_types=1);

namespace Lyrica0954\Ranking;

use Closure;
use pocketmine\utils\Utils;

class RankingManager {

	/**
	 * @var array<string, Ranking>
	 */
	protected array $list;

	/**
	 * @var Ranking[]
	 */
	protected array $sorted;

	public function __construct() {
		$this->list = [];
		$this->sorted = [];
	}

	public function update(Closure $consumer): void {
		Utils::validateCallableSignature(function (array $list, RankingManager $manager): void {
		}, $consumer);

		$consumer($this->list, $this);

		$this->sort();
	}

	/**
	 * @return array<int, Ranking>
	 */
	public function getSorted(): array {
		if (!$this->isSorted()) {
			$this->sort();
		}

		return $this->sorted;
	}

	public function isSorted(): bool {
		return count($this->sorted) === count($this->list);
	}

	/**
	 * @return array<string, Ranking>
	 */
	public function getAll(): array {
		return $this->list;
	}

	public function register(Ranking $ranking): bool {
		if (isset($this->list[$ranking->getName()])) {
			return false;
		}

		$this->internalAdd($ranking);

		return true;
	}

	public function unregister(string $name): void {
		$this->internalRemove($name);
	}

	protected function internalRemove(string $name): void {
		unset($this->list[$name]);

		$this->sorted = [];
	}

	protected function internalAdd(Ranking $ranking): void {
		$this->list[$ranking->getName()] = $ranking;

		$this->sorted = [];
	}

	protected function sort(): void {
		$this->sorted = [];

		$sorted = $this->list;
		usort($sorted, Ranking::manualSort());

		$this->sorted = $sorted;
	}

	/**
	 * @param array<int, Ranking> $list
	 * @param string $format
	 * @param int $count
	 * 
	 * @return array
	 */
	public static function format(array $list, string $format, int $count): array {
		$result = [];
		$current = 0;
		foreach ($list as $index => $ranking) {
			$result[] = sprintf($format, $index + 1, $ranking->getDisplayName(), $ranking->getValue(), $ranking->getName());

			$current++;
			if ($current >= $count) {
				break;
			}
		}

		return $result;
	}

	public function getTopRanking(): ?Ranking {
		return count($this->sorted) > 0 ? ($this->sorted[array_key_first($this->sorted)]) : null;
	}
}
