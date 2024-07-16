<?php

namespace SkyWars\Sessions\interface;

use pocketmine\player\Player;

interface SessionInterface
{

    public function initializeDataFolder(Player $player):string;

    public function getPlayer();
    public function getDataFolder();

}