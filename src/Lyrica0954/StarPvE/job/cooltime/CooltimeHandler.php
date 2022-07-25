<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\cooltime;

use Lyrica0954\StarPvE\event\cooltime\CooltimeFinishEvent;
use Lyrica0954\StarPvE\event\cooltime\CooltimeStartEvent;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class CooltimeHandler {

    const BASE_SECOND = 20;
    const BASE_TICK = 1;

    protected int $remain;

    private int $baseTick;
    private int $speed;
    private ?Task $task;
    private ?CooltimeAttachable $attached;

    private bool $active;
    private string $id;

    public function __construct(string $id, int $baseTick, int $speed = 1) {
        $this->id = $id;
        $this->remain = 0;
        $this->time = 0;
        $this->active = false;
        $this->baseTick = $baseTick;
        $this->speed = $speed;
        $this->task = null;

        $this->attached = null;
    }

    public function getId() {
        return $this->id;
    }

    public function getRemain() {
        return (int) $this->remain / $this->baseTick;
    }

    public function getTime() {
        return (int) $this->time / $this->baseTick;
    }

    public function getPercentage() {
        return ($this->remain / $this->time) * 100;
    }

    public function attach(CooltimeAttachable $object) {
        $this->attached = $object;
    }

    public function detach() {
        $this->attached = null;
    }

    public function set(int $cooltime) {
        $this->remain = $cooltime;
        $this->time = $cooltime;
    }

    public function reset(): void {
        $this->remain = $this->time;
    }

    public function calculate(int $cooltime): int {
        return (int) $cooltime / $this->baseTick;
    }

    public function add(int $cooltime) {
        $this->remain += $cooltime;
    }

    public function subtract(int $cooltime) {
        $this->remain -= $cooltime;
        if ($this->remain <= 0) {
            $this->finished();
        }
    }

    public function getBaseTick() {
        return $this->baseTick;
    }

    public function getSpeed() {
        return $this->speed;
    }

    public function getPeriod() {
        return $this->baseTick * $this->speed;
    }

    public function isActive() {
        return $this->active;
    }

    public function tick() {
        $remain = ($this->remain - $this->getPeriod()) / 20;
        $passed = $this->attached?->cooltimeTick($this, (int) $remain);
        if ($this->attached === null) $passed = true;

        if ($passed) $this->subtract($this->getPeriod());
    }

    public function start(int $cooltime) {
        $this->log("Started the Task");
        $ev = new CooltimeStartEvent($this, $cooltime);
        $ev->call();
        if (!$ev->isCancelled()) {
            $cooltime = $ev->getCooltime();
            $this->remain = $cooltime;
            $this->time = $cooltime;
            $this->active = true;
            $this->task = new class($this) extends Task {

                private CooltimeHandler $cooltimeHandler;

                public function __construct(CooltimeHandler $cooltimeHandler) {
                    $this->cooltimeHandler = $cooltimeHandler;
                }

                public function onRun(): void {
                    $this->cooltimeHandler->tick();
                }
            };
            StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask($this->task, $this->getPeriod());
        }
    }

    public function __destruct() {
        $this->forceStop();
    }

    public function pause() {
        if ($this->active) {
            $this->task?->getHandler()->setNextRun(PHP_INT_MAX - 1);
        }
    }

    public function resume() {
        if ($this->active) {
            $this->task?->getHandler()->setNextRun($this->getPeriod());
        }
    }

    public function stop() {
        if ($this->active) {
            $this->finished();
        }
    }

    public function log(string $message) {
        StarPvE::getInstance()->log("§7[CooltimeHandler - {$this->id}] §7{$message}");
    }

    public function forceStop() {
        $this->task?->getHandler()?->cancel();
        $this->active = false;
        $this->time = 0;
        $this->remain = 0;
    }

    protected function finished(): void {
        if ($this->task instanceof Task) {
            $ev = new CooltimeFinishEvent($this);
            $ev->call();
            $this->time = 0;
            $this->remain = 0;
            $this->attached?->cooltimeFinished($this);
            $this->task->getHandler()->cancel();
            $this->log("Stopped the Task");
            $this->active = false;
        }
    }
}
