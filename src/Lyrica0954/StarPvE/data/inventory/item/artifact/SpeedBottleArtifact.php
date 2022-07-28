<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item\artifact;

use Lyrica0954\StarPvE\data\inventory\item\ArtifactItem;
use Lyrica0954\StarPvE\identity\player\SpeedPercentageArgIdentity;

class SpeedBottleArtifact extends ArtifactItem {

    public function getName(): string {
        return "スピード瓶";
    }

    public function getDescription(): string {
        return "スピードが上昇する";
    }

    protected function init(): void {
        $this->identityGroup->add(new SpeedPercentageArgIdentity(null, 1.05));
    }
}
