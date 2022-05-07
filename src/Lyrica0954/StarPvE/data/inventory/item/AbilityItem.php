<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item;

use Lyrica0954\StarPvE\data\inventory\InvItem;
use Lyrica0954\StarPvE\identity\IdentityGroup;

abstract class AbilityItem extends InvItem {

	protected IdentityGroup $identityGroup;

	public function __construct(int $id, string $name){
		parent::__construct($id, $name);

		$this->identityGroup = new IdentityGroup;
	}

	public function getIdentityGroup(): IdentityGroup{
		return $this->identityGroup;
	}
}