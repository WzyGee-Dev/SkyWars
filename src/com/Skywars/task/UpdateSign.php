<?php

namespace com\Skywars\task;

use com\Skywars\arena\sign\SignManager;
use pocketmine\scheduler\Task;

class UpdateSign extends Task
{


    public SignManager $signManager;

    public function __construct(SignManager $signManager)
    {
        $this->signManager = $signManager;
    }

    public function onRun(): void
    {
       $this->signManager->updateSigns();
    }
}