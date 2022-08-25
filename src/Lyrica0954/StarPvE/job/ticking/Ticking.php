<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\ticking;

interface Ticking {

	public function onTick(string $id, int $tick): void;
}
