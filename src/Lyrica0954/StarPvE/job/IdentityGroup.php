<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\player\Player;

class IdentityGroup {

    /**
     * @var Identity[]
     */
    protected array $identities;

    public function __construct(Identity... $identities){
        $this->identities = $identities;
    }

    public function reset(PlayerJob $playerJob): void{
        foreach($this->identities as $identity){
            if ($identity->isActivateable($playerJob->getPlayer())){
                $identity->reset($playerJob);
            }
        }
    }

    public function apply(PlayerJob $playerJob): void{
        foreach($this->identities as $identity){
            if ($identity->isActivateable($playerJob->getPlayer())){
                $identity->apply($playerJob);
            }
        }
    }

    public function close(): void{
        foreach($this->identities as $identity){
            $identity->close();
        }
    }

    /**
     * @param Identity[] $identities
     * 
     * @return void
     */
    protected function setIdentities(array $identities): void{
        $this->identities = $identities;
    }

    /**
     * @return Identity[]
     */
    public function getAll(): array{
        return $this->identities;
    }
    
    /**
     * @param Player $player
     * 
     * @return Identity[]
     */
    public function getActive(Player $player): array{
        $active = [];
        foreach($this->identities as $identity){
            if ($identity->isActivateable($player)){
                $active[] = $identity;
            }
        }
        return $active;
    }

    public function get(int $key): ?Identity{
        return $this->identities[$key] ?? null;
    }

    public function search(string $name): ?Identity{
        foreach($this->identities as $identity){
            if ($identity->getName() == $name){
                return $identity;
            }
        }
        return null;
    }
}