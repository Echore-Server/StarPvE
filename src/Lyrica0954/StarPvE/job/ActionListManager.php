<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use pocketmine\block\Planks;
use pocketmine\player\Player;

class ActionListManager {

	const MODE_STRICT = 0;

	const MODE_DOWN = 1;

	const MODE_UP = 1;

	/**
	 * @var LineOption[]
	 */
	protected array $lines;

	protected int $mode;

	protected int $max;

	protected int $min;

	protected int $defaultDuration;

	protected bool $changed;

	public function __construct(int $mode = self::MODE_DOWN, int $defaultDuration = 20) {
		$this->max = 5;
		$this->min = 0;
		$this->lines = [];
		$this->allocateLines();
		$this->mode = $mode;
		$this->defaultDuration = $defaultDuration;

		$this->changed = true;
	}

	public function default(LineOption $option) {
		$option->setDuration($option->getDuration() == 0 ? $this->defaultDuration : $option->getDuration());
	}

	public function getMax(): int {
		return $this->max;
	}

	public function setMax(int $max): void {
		$this->max = $max;
		$this->allocateLines();
	}

	protected function internalSetLine(int $line, LineOption $option): void {
		$this->lines[$line] = $option;
		$this->changed = true;
	}

	public function allocateLines(): void {
		foreach (range($this->min, $this->max) as $line) {
			if (!isset($this->lines[$line])) {
				$this->lines[$line] = LineOption::none();
			}
		}
	}

	public function hasContent(): bool {
		foreach ($this->lines as $line) {
			if ($line->getText() !== "") {
				return true;
			}
		}
		return false;
	}

	public function getMin(): int {
		return $this->min;
	}

	public function setMin(int $min): void {
		$this->min = $min;
		$this->allocateLines();
	}

	public function setLine(int $line, LineOption $option): void {
		$this->default($option);

		$this->internalSetLine($line, $option);

		$this->lines = $this->process($this->lines, $this->mode);
		$this->changed = true;
	}

	public function getMode(): int {
		return $this->mode;
	}

	public function push(LineOption $option): void {
		$this->default($option);

		$line = match ($this->mode) {
			self::MODE_DOWN => $this->min,
			self::MODE_UP => $this->max,
			self::MODE_STRICT => 0,
			default => 0
		};
		$this->setLine($line, $option);
	}

	public function getLine(int $line): ?LineOption {
		return $this->lines[$line] ?? null;
	}

	/**
	 * @return string[]
	 */
	public function getAll(): array {
		return $this->lines;
	}

	/**
	 * @return LineOption[]
	 */
	public function getSorted(): array {
		$sorted = [];
		$immobiles = [];

		foreach ($this->lines as $line => $option) {
			$d = $option->getDuration();
			if ($d < 0 && $this->mode == self::MODE_DOWN) {
				$d = 10000000;
			}

			if (!$option->isImmobile()) {
				$sorted[$line] = $d;
			} else {
				$immobiles[$line] = $d;
			}
		}


		arsort($sorted, SORT_NUMERIC);
		foreach ($immobiles as $line => $d) {
			$sorted[$line] = $d;
		}
		return $sorted;
	}

	/**
	 * @param LineOption[] $lines
	 * @param int $mode
	 * 
	 * @return LineOption[]
	 */
	public function process(array $lines, int $mode): array {
		if ($mode === self::MODE_DOWN) {
			$sorted = $this->getSorted();
			$c = 0;
			$copyLines = $lines;
			foreach ($sorted as $line => $duration) {
				$lines[$c] = $copyLines[$line];
				$c++;
			}
		} elseif ($mode === self::MODE_UP) {
			$sorted = array_reverse($this->getSorted(), true);
			$c = 0;
			$copyLines = $lines;
			foreach ($sorted as $line => $duration) {
				$lines[$c] = $copyLines[$line];
				$c++;
			}
		}

		return $lines;
	}

	public function update(int $tick): void {
		$this->changed = false;
		$this->decrease($tick);
		$this->lines = $this->process($this->lines, $this->mode);
	}

	public function hasChanged(): bool {
		return $this->changed;
	}

	public function setChanged(bool $changed = true): void {
		$this->changed = $changed;
	}

	public function getText(): string {
		$text = "";
		foreach ($this->lines as $line => $option) {
			$text .= $option->getText();

			$text .= "\n";
		}

		return $text;
	}

	public function decrease(int $decrease): void {
		foreach ($this->lines as $line => $option) {
			if ($option->getDuration() > 0) {
				$option->setDuration($option->getDuration() - $decrease);
				if ($option->getDuration() <= 0) {
					$this->internalSetLine($line, LineOption::none());
				}
			}
		}
	}
}
