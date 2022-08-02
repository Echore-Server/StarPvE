<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Exception;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

abstract class Ability {

    /**
     * @var AbilityStatus
     */
    protected AbilityStatus $damage;
    /**
     * @var AbilityStatus
     */
    protected AbilityStatus $area;
    /**
     * @var AbilityStatus
     */
    protected AbilityStatus $speed;
    /**
     * @var AbilityStatus
     */
    protected AbilityStatus $duration;
    /**
     * @var AbilityStatus
     */
    protected AbilityStatus $amount;
    /**
     * @var AbilityStatus
     */
    protected AbilityStatus $percentage;

    protected bool $active;

    protected bool $closed;

    /**
     * @var PlayerJob
     */
    protected PlayerJob $job;

    /**
     * @var Player|null
     */
    protected ?Player $player;

    /**
     * @var CooltimeHandler
     */
    protected CooltimeHandler $cooltimeHandler;

    /**
     * @var TaskHandler[]
     */
    protected array $tasks;

    public function __construct(PlayerJob $job) {
        $this->job = $job;
        $this->player = $job->getPlayer();
        $this->closed = false;
        $this->active = false;
        $this->cooltimeHandler = new CooltimeHandler("アビリティ", CooltimeHandler::BASE_TICK, 1);

        if ($this->player instanceof Player) {
            if ($this instanceof Listener) Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
        }

        $this->damage = new AbilityStatus(0.0);
        $this->area = new AbilityStatus(0.0);
        $this->speed = new AbilityStatus(0.0);
        $this->duration = new AbilityStatus(0.0);
        $this->amount = new AbilityStatus(0.0);
        $this->percentage = new AbilityStatus(0.0);

        $this->tasks = [];

        $this->init();
    }

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract protected function init(): void;

    /**
     * @return TaskHandler[]
     */
    public function getTasks(): array {
        return $this->tasks;
    }

    public function registerTask(TaskHandler $handler): void {
        $this->tasks[] = $handler;
    }

    public function close(): void {
        $this->cooltimeHandler->forceStop();
        foreach ($this->tasks as $handler) {
            $handler->cancel();
        }
        if ($this instanceof Listener) HandlerListManager::global()->unregisterAll($this);
    }

    public function isActive(): bool {
        return $this->active;
    }

    public function getCooltimeHandler(): CooltimeHandler {
        return $this->cooltimeHandler;
    }

    public function getPlayer(): ?Player {
        return $this->player;
    }

    public function getJob(): PlayerJob {
        return $this->job;
    }

    public function getDamage(): AbilityStatus {
        return $this->damage;
    }

    public function getArea(): AbilityStatus {
        return $this->area;
    }

    public function getSpeed(): AbilityStatus {
        return $this->speed;
    }

    public function getDuration(): AbilityStatus {
        return $this->duration;
    }

    public function getAmount(): AbilityStatus {
        return $this->amount;
    }

    public function getPercentage(): AbilityStatus {
        return $this->percentage;
    }

    abstract public function getCooltime(): int;

    public function activate(): ActionResult {
        if (!$this->closed) {
            if (!$this->cooltimeHandler->isActive()) {
                if (!$this->active) {
                    if ($this->getCooltime() > 0) {
                        $this->cooltimeHandler->start($this->getCooltime());
                    }
                    $result = $this->onActivate();

                    if ($result->isMiss()) {
                        $this->cooltimeHandler->stop();
                    }

                    return $result;
                } else {
                    return ActionResult::FAILED_ALREADY_ACTIVE();
                }
            } else {
                return ActionResult::FAILED_BY_COOLTIME();
            }
        } else {
            throw new \Exception("cannot activate closed ability");
        }
    }

    abstract protected function onActivate(): ActionResult;
}
