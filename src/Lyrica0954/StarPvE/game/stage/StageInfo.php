<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\stage;

use Lyrica0954\StarPvE\identity\IdentityGroup;
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

	protected IdentityGroup $identityGroup;

	public function __construct(
		string $name,
		Vector3 $center,
		Vector3 $lane1,
		Vector3 $lane2,
		Vector3 $lane3,
		Vector3 $lane4,
		string $worldName,
		string $author,
		IdentityGroup $identityGroup
	) {
		$this->name = $name;
		$this->center = $center;
		$this->lane1 = $lane1;
		$this->lane2 = $lane2;
		$this->lane3 = $lane3;
		$this->lane4 = $lane4;
		$this->worldName = $worldName;
		$this->author = $author;
		$this->identityGroup = $identityGroup;
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

	public function getIdentityGroup(): IdentityGroup {
		return $this->identityGroup;
	}
}
