<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\ranking;

use Closure;
use Lyrica0954\Ranking\RankingManager;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class RankingEntry {

	public function __construct(
		protected RankingManager $manager,
		protected string $name,
		protected Vector3 $position,
		protected string $format,
		protected int $defaultCount = PHP_INT_MAX,
		protected Closure $updateConsumer,
	) {
	}

	public function update(): void {
		$this->manager->update($this->updateConsumer);
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return RankingManager
	 */
	public function getManager(): RankingManager {
		return $this->manager;
	}

	/**
	 * @return Vector3
	 */
	public function getPosition(): Vector3 {
		return $this->position;
	}

	/**
	 * @return string
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * @param string $format
	 * 
	 * @return self
	 */
	public function setFormat(string $format): self {
		$this->format = $format;

		return $this;
	}


	public function format(?int $count = null): string {
		return join("\n", RankingManager::format($this->manager->getSorted(), $this->format, $count ?? $this->defaultCount));
	}
}
