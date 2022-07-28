<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\StarPvE;

class CommandLoader {

    public static function load(StarPvE $p) {
        new HubCommand("hub", $p, $p);
        new GameCommand("game", $p, $p);
        new TestCommand("testf", $p, $p);
        new PlayerStatusCommand("stats", $p, $p);
        new SettingCommand("setting", $p, $p);

        new TaskInfoCommand("taskinfo", $p, $p);
    }
}
