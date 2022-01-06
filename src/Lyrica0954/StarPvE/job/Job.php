<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

abstract class Job {

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function getAbilityName(): string;

    abstract public function getAbilityDescription(): string;

    abstract public function getSkillName(): string;

    abstract public function getSkillDescription(): string;
}