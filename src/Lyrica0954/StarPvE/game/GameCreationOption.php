<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

use Lyrica0954\StarPvE\game\stage\StageFactory;

class GameCreationOption {

	protected string $id;

	protected string $stageName;

	protected GameOption $gameOption;

	public static function genId(int $length): string {
		return substr(str_shuffle("qwertyuiopasdfghjklzxcvbnm1234567890"), 0, $length);
	}

	public static function manual(?string $stageName = null, ?string $id = null, ?GameOption $gameOption = null): self {
		$stageNames = array_keys(StageFactory::getInstance()->getList());
		$stageName = $stageName ?? $stageNames[array_rand($stageNames)];
		if ($gameOption === null) {
			$gameOption = GameOption::manual();
		}
		return new self($id ?? self::genId(10), $stageName, $gameOption);
	}


	public function __construct(string $id, string $stageName, GameOption $gameOption) {
		$this->id = $id;
		$this->stageName = $stageName;
		$this->gameOption = $gameOption;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getStageName(): string {
		return $this->stageName;
	}

	public function getGameOption(): GameOption {
		return $this->gameOption;
	}
}
