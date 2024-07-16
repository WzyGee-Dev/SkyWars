<?php

namespace SkyWars\Sessions\interface;

use pocketmine\player\Player;

abstract class AbstractSession implements SessionInterface
{

    protected Player $player;

    protected string $datafolder;


    public function __construct(Player $player)
    {
        $this->player = $player;
        $this->datafolder = $this->initializeDataFolder($player);
    }

    abstract public function initializeDataFolder(Player $player): string;
    public function getDatafolder(): string
    {
        return $this->datafolder;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }


}