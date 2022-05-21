<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

class LineOption {

	public int $duration;

	protected string $text;

	protected bool $immobile;

	public function __construct(string $text, int $duration = 0) {
		$this->duration = $duration;
		$this->text = $text;
		$this->immobile = false;
	}

	public static function none(): LineOption {
		$c = new self("", -1);
		return $c;
	}

	public static function immobile(string $text, int $duration = 0): LineOption {
		$c = new self($text, $duration);
		$c->immobile = true;
		return $c;
	}

	public function isImmobile(): bool {
		return $this->immobile;
	}

	public function setImmobile(bool $immobile): void {
		$this->immobile = $immobile;
	}

	public function getText(): string {
		return $this->text;
	}

	public function setText(string $text): void {
		$this->text = $text;
	}

	public function getDuration(): int {
		return $this->duration;
	}

	public function setDuration(int $duration): void {
		$this->duration = $duration;
	}

	public function canPass(): bool {
		return ($this->immobile);
	}
}
