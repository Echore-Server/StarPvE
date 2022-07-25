<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

interface AlwaysAbility {

    public function getAlAbilityName(): string;

    public function getAlAbilityDescription(): string;
}
