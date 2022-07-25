<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use pocketmine\plugin\PluginBase;

class ParticleHost {

    protected PluginBase $plugin;

    protected ParticleSender $sender;

    public function __construct(PluginBase $plugin, ParticleSender $sender) {
        $this->plugin = $plugin;
        $this->sender = $sender;
    }

    public function getPlugin(): PluginBase {
        return $this->plugin;
    }

    public function getSender(): ParticleSender {
        return $this->sender;
    }
}
