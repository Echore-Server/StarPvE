<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\task\TaskHolder;
use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\Random;
use Lyrica0954\StarPvE\utils\RandomUtil;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\item\Armor;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

class WaveMonsters {

    /**
     * @var MonsterData[]
     */
    private array $monsters;

    public function __construct(MonsterData ...$monsters){
        $this->monsters = $monsters;
    }

    /**
     * @return MonsterData[]
     */
    public function getAll(): array{
        return $this->monsters;
    }

    public function log(string $message){
        StarPvE::getInstance()->log("§7[WaveMonsters] {$message}");
    }

    public function spawnToAll(Position $pos, array $attributeMap, array $equipmentMap, \Closure $hook = null){
        foreach($this->monsters as $monster){
            $class = $monster->name;
            $time = 90 * 20;
            $period = (integer) floor($time / $monster->count);
            $attribute = $attributeMap[$class];
            $equipment = $equipmentMap[$class];
            StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask(new class($monster, $pos, $attribute, $equipment, $hook) extends Task{
                
                private MonsterData $monster;
                private MonsterAttribute $attribute;
                private ArmorSet $equipment;
                private Position $pos;
                private ?\Closure $hook;
                private int $count;

                public function __construct(MonsterData $monster, Position $pos, MonsterAttribute $attribute, ArmorSet $equipment, ?\Closure $hook){
                    $this->monster = $monster;
                    $this->attribute = $attribute;
                    $this->equipment = $equipment;
                    $this->pos = $pos;
                    $this->hook = $hook;
                    $this->count = 0;
                }

                public function onRun(): void{
                    if ($this->pos->world !== null && $this->pos->world?->isLoaded()){
                        $this->count ++;
                        $class = $this->monster->name;
                        $loc = new Location($this->pos->x, $this->pos->y, $this->pos->z, $this->pos->getWorld(), 0, 0);
                        $loc->x += RandomUtil::rand_float(-1.75, 1.75);
                        $loc->z += RandomUtil::rand_float(-1.75, 1.75);
                        $entity = new $class($loc);
                        $this->attribute->apply($entity);
                        $this->equipment->equip($entity);
                        $defAnimation = new SpawnAnimation(function(){return false;}, 1);
                        $defAnimation->setInitiator(function(Living $entity){
                            $pos = $entity->getPosition();
                            $pos->x += RandomUtil::rand_float(-1.75, 1.75);
                            $pos->z += RandomUtil::rand_float(-1.75, 1.75);
                            $entity->teleport($pos);
                        });
                        $animation = $this->monster->animation ?? $defAnimation;
                        $animation->spawn($entity);
                        if ($this->hook !== null){
                            $h = $this->hook;
                            $h($entity);
                        }
                        if ($this->count >= $this->monster->count){
                            $this->getHandler()->cancel();
                            StarPvE::getInstance()->log("§7[WaveMonsters] Removed Monster Spawner: Successfly Spawned");
                        }
                    } else {
                        $this->getHandler()->cancel();
                        StarPvE::getInstance()->log("§7[WaveMonsters] Removed Monster Spawner: World Unloaded");
                    }
                }
            }, $period);
            $this->log("Added Monster Spawner");
        }
    }
}