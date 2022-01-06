<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE;

use pocketmine\plugin\PluginBase;

final class StarPvE extends PluginBase {

    private static ?StarPvE $instance = null;

    public static function getInstance(): ?StarPvE{
        return self::$instance;
    }

    protected function onLoad(): void{
        self::$instance = $this;
    }
}