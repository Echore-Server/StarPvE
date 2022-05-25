<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\player\Player;

class AddMaxHealthArgIdentity extends PlayerArgIdentity {

    protected int $add;

    public function __construct(?Condition $condition = null, int $add) {
        parent::__construct($condition);
        $this->add = $add;
    }

    public function getName(): string {
        return "最大HP増加";
    }

    public function getDescription(): string {
        return "最大HPが {$this->add} 増加";
    }

    public function apply(): void {
        if ($this->player !== null) {
            EntityUtil::addMaxHealthSynchronously($this->player, $this->add);
        }
    }

    public function reset(): void {
        if ($this->player !== null) {
            EntityUtil::addMaxHealthSynchronously($this->player, -$this->add);
        }
    }
}
