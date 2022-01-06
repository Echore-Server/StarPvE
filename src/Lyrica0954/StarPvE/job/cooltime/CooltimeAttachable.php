<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\cooltime;

interface CooltimeAttachable {

    public function cooltimeTick(CooltimeHandler $cooltimeHandler): void;

    public function cooltimeFinished(CooltimeHandler $cooltimeHandler): void;
}