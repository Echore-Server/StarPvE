<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\player\PlayerJob;

class IdentitySpell implements Spell {

	protected PlayerJob $job;
	protected IdentityGroup $identityGroup;

	protected string $name;

	public function __construct(PlayerJob $job, string $name) {
		$this->job = $job;
		$this->identityGroup = new IdentityGroup();
		$this->name = $name;
	}

	public function isApplicable(): bool {
		return count($this->identityGroup->getApplicable()) > 0;
	}

	public function getIdentityGroup(): IdentityGroup {
		return $this->identityGroup;
	}

	public function addIdentity(Identity $identity): self {
		$this->identityGroup->add($identity);
		return $this;
	}

	public function getName(): string {
		return $this->name;
	}

	public function close(): void {
		$this->identityGroup->close();
	}
}
