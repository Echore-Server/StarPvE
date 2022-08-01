<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Lyrica0954\MagicParticle\effect\ParticleEffect;
use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\SettingVariables;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

class ParticleSender {

    /**
     * @var int[]
     */
    protected array $particleCount;

    /**
     * @var int[]
     */
    protected array $lastSend;

    public function __construct() {
        $this->particleCount = [];
        $this->lastSend = [];
    }

    public function check(Player $player, Vector3 $pos, int $maxRange = 16): ?Vector3 {
        return ($player->canInteract($pos, $maxRange, M_SQRT3 / 3)) ? $pos : null;
    }

    public function send(Player $player, Vector3 $pos, ClientboundPacket $packet) {
        if ($this->check($player, $pos)) {
            $h = spl_object_hash($player);
            $lastSend = $this->lastSend[$h] ?? 0;
            $tick = Server::getInstance()->getTick();

            $ppt = $this->particleCount[$h] ?? 0;
            $adapter = SettingVariables::fetch($player);
            if ($adapter instanceof PlayerConfigAdapter) {
                $limit = $adapter->getConfig()->get(SettingVariables::PARTICLE_PER_TICK, 0);
            } else {
                $limit = 0;
            }

            if ($tick - $lastSend >= 1) {
                $this->particleCount[$h] = 0;

                $this->lastSend[$h] = $tick;
            }

            if ($ppt >= $limit) {
                return;
            }

            $this->particleCount[$h] ?? $this->particleCount[$h] = 0;
            $this->particleCount[$h]++;


            $player->getNetworkSession()->addToSendBuffer($packet);
        }
    }

    public function sendParticle(SendableParticle $particle, array $players, Position $pos, ParticleOption $option) {
        foreach ($particle->getPackets($pos, $option) as $packed) {
            foreach ($packed as $pk) {
                foreach ($players as $player) {
                    if ($player instanceof Player) {
                        $this->send($player, $pk->position, $pk);
                    }
                }
            }
        }
    }

    public function sendEffect(ParticleEffect $effect, array $players, Position $pos, ParticleOption $option) {
        foreach ($effect->draw($pos) as $coveredParticle) {
            if ($coveredParticle instanceof CoveredParticle) {
                if ($coveredParticle instanceof PartDelayedParticle) {
                    $this->sendPartDelayed($coveredParticle, $players, $option);
                } else {
                    $particle = $coveredParticle->getParticle();
                    $this->sendParticle($particle, $players, $coveredParticle->getPosition(), $option);
                }
            }
        }
    }

    public function sendPartDelayed(PartDelayedParticle $particle, array $players, ParticleOption $option) {
        $parts = $particle->getPackets($option);

        foreach ($particle->getPartHooks() as $hook) {
            $parts = ($hook)($parts);
        }

        $progress = new \stdClass;
        $progress->offset = $particle->getOffset();

        $limit = (int) floor(count($parts) / $particle->getPartLength());
        TaskUtil::repeatingClosureLimit(function () use ($players, $parts, $particle, $progress) {
            $partLength = $particle->getPartLength();

            $progress->offset += $partLength;
            $slicedPart = array_slice($parts, $progress->offset, $partLength);
            foreach ($slicedPart as $packed) {
                /**
                 * @var Packet[] $packed
                 */
                foreach ($packed as $pk) {
                    foreach ($particle->getDrawHooks() as $hook) {
                        ($hook)($pk->position);
                    }
                    foreach ($players as $player) {
                        if ($player instanceof Player && $player->isOnline()) {
                            $this->send($player, $pk->position, $pk);
                        }
                    }
                }
            }
        }, $particle->getPeriod(), $limit);
    }
}
