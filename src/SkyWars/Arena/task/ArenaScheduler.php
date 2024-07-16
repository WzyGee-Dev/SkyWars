<?php

namespace SkyWars\Arena\task;

use pocketmine\scheduler\Task;
use SkyWars\Arena\Arena;

class ArenaScheduler extends Task
{

    public Arena $arena;

    public function __construct(Arena $arena)
    {
        $this->arena = $arena;
    }

    public function onRun(): void
    {

    }
}