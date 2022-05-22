<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\stage;

use pocketmine\math\Vector3;

class StageInfo {

	protected string $name;

	protected Vector3 $center;

	protected Vector3 $lane1;

	protected Vector3 $lane2;

	protected Vector3 $lane3;

	protected Vector3 $lane4;

	protected string $worldName;

	protected string $author;

	public function __construct(
		string $name,
		Vector3 $center,
		Vector3 $lane1,
		Vector3 $lane2,
		Vector3 $lane3,
		Vector3 $lane4,
		string $worldName,
		string $author
	) {
		$this->name = $name;
		$this->center = $center;
		$this->lane1 = $lane1;
		$this->lane2 = $lane2;
		$this->lane3 = $lane3;
		$this->lane4 = $lane4;
		$this->worldName = $worldName;
		$this->author = $author;
	}

	public static function parseData(array $json): self {
		return new StageInfo(
			$json["name"],
			self::solveVector3($json["center"]),
			self::solveVector3($json["lane1"]),
			self::solveVector3($json["lane2"]),
			self::solveVector3($json["lane3"]),
			self::solveVector3($json["lane4"]),
			$json["worldName"],
			$json["author"]
		);
	}

	public static function solveVector3(array $json): Vector3 {
		return new Vector3(
			$json["x"],
			$json["y"],
			$json["z"]
		);
	}

	public function getName(): string {
		return $this->name;
	}

	public function getCenter(): Vector3 {
		return $this->center;
	}

	public function getLane1(): Vector3 {
		return $this->lane1;
	}

	public function getLane2(): Vector3 {
		return $this->lane2;
	}

	public function getLane3(): Vector3 {
		return $this->lane3;
	}

	public function getLane4(): Vector3 {
		return $this->lane4;
	}

	public function getWorldName(): string {
		return $this->worldName;
	}

	public function getAuthor(): string {
		return $this->author;
	}
}
