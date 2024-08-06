<?php

namespace com\Skywars\task;

use com\Skywars\arena\Arena;
use pocketmine\scheduler\Task;

class GameTask extends Task
{

    public Arena $arena;

    public function __construct(Arena $arena)
    {
        $this->arena = $arena;
    }

    public function onRun(): void
    {
        $this->arena->tick();
    }


    public function onCancel(): void
    {
        $this->getHandler()->cancel();
    }
}