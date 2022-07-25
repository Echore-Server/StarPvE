<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\player\PlayerJob;

abstract class Skill extends Ability {

    public function __construct(PlayerJob $job) {
        parent::__construct($job);
        $this->cooltimeHandler = new CooltimeHandler("スキル", CooltimeHandler::BASE_TICK, 1);
    }
}
