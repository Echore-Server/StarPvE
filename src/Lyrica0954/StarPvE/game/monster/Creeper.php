<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\walking\Creeper as SmartCreeper;
use Lyrica0954\StarPvE\utils\HealthBarEntity;

class Creeper extends SmartCreeper {
    use HealthBarEntity;
}