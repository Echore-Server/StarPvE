<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster\boss;

use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\game\wave\SpawnAnimation;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use pocketmine\entity\Living;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class ZombieLord extends SmartZombie {
    use HealthBarEntity;

    protected float $reach = 1.5;

	protected int $callTick = 0;

    public function getFollowRange(): float{
        return 50;
    }

	protected function initEntity(CompoundTag $nbt): void{
		parent::initEntity($nbt);

		$this->setScale(1.5);
	}

	protected function callZombie(){
		$tick = 50;
		$std = new \stdClass;
		$std->y = 0;
		$std->step = 0;
		$std->tick = 0;
		$animation = new SpawnAnimation(function(Living $entity){return false;}, 1);
		$animation->setInitiator(function(Living $entity){
			$motion = EntityUtil::modifyKnockback($entity, $this, 1.8, 1.0);
			$entity->setMotion($motion);
		});

		$monsters = new WaveMonsters(new MonsterData(MonsterData::ZOMBIE, 1, $animation));
		
		$game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
		if ($game instanceof Game){
			if (!$game->isClosed()){
				$game->getWaveController()->spawnMonster($monsters, $this->getPosition());
			}
		}

		(new SingleParticle)->sendToPlayers($this->getWorld()->getPlayers(), $this->getPosition(), "minecraft:knockback_roar_particle");
	}

	protected function entityBaseTick(int $tickDiff = 1): bool{
		$update = parent::entityBaseTick($tickDiff);


		$this->callTick += $tickDiff;
		if ($this->callTick >= 200){
			$this->callTick = 0;
			$this->callZombie();
		}

		return $update;
	}
}