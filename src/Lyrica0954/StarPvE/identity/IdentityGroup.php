<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity;

use Lyrica0954\StarPvE\identity\player\PlayerArgIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\player\Player;

class IdentityGroup {

    /**
     * @var Identity[]
     */
    protected array $identities;

    public function __construct() {
        $this->identities = [];
    }

    public function add(Identity $identity): void {
        $this->identities[] = $identity;
    }

    /**
     * @param Identity[] $list
     * 
     * @return void
     */
    public function addAll(array $list): void {
        foreach ($list as $identity) {
            $this->add($identity);
        }
    }

    /**
     * @param (Identity|null)[] $list
     * 
     * @return void
     */
    public function addAllSafe(array $list): void {
        foreach ($list as $identity) {
            if ($identity instanceof Identity) {
                $this->add($identity);
            }
        }
    }

    public function reset(): void {
        foreach ($this->identities as $identity) {
            if ($identity->isApplicable()) {
                $identity->reset();
            }
        }
    }

    public function apply(): void {
        foreach ($this->identities as $identity) {
            if ($identity->isApplicable()) {
                $identity->apply();
            }
        }
    }

    public function close(): void {
        foreach ($this->identities as $identity) {
            $identity->close();
        }
    }

    /**
     * @param Identity[] $identities
     * 
     * @return void
     */
    protected function setIdentities(array $identities): void {
        $this->identities = $identities;
    }

    /**
     * @return Identity[]
     */
    public function getAll(): array {
        return $this->identities;
    }

    /**
     * @param Player $player
     * 
     * @return Identity[]
     */
    public function getApplicable(): array {
        $applicable = [];
        foreach ($this->identities as $identity) {
            if ($identity->isApplicable()) {
                $applicable[] = $identity;
            }
        }
        return $applicable;
    }

    public function get(int $key): ?Identity {
        return $this->identities[$key] ?? null;
    }

    public function search(string $name): ?Identity {
        foreach ($this->identities as $identity) {
            if ($identity->getName() == $name) {
                return $identity;
            }
        }
        return null;
    }
}
