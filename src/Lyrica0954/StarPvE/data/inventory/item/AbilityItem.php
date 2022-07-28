<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item;

use Lyrica0954\StarPvE\identity\IdentityGroup;

abstract class AbilityItem extends InvItem {

	protected IdentityGroup $identityGroup;

	public function __construct(int $id) {
		parent::__construct($id);

		$this->identityGroup = new IdentityGroup;

		$this->init();
	}

	abstract protected function init(): void;

	public function getIdentityGroup(): IdentityGroup {
		return $this->identityGroup;
	}
}
