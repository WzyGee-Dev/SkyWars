<?php

namespace SkyWars\Arena\extends;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SkyWars\Loader;
use SkyWars\Sessions\SessionManager;

class ArenaSetup
{

    public static array $spawnsPoint = [];
    public static function registerSpawn(Player $player, Vector3 $pos): void
    {
        $playerName = $player->getName();
        if (!isset(self::$spawnsPoint[$playerName])){
            self::$spawnsPoint = [];
        }
        self::$spawnsPoint[$playerName][] = $pos;
        $spawnIndex = count(self::$spawnsPoint[$playerName]) + 1;
        $player->sendMessage('Spawn point '. $spawnIndex . ' registered at '. $pos->asVector3()->__toString());
        $arenaName = SessionManager::getSession($player)->getDevelopedArena();
        if($arenaName !== null){
            $arena = Loader::getLoader()->getArenaManager()->getArena($arenaName);
            if($arena !== null && count(self::$spawnsPoint[$playerName]) >= $arena->getMaxplayer()){
                self::saveArenaSpawns($player);
            }
        }
    }

    private static function saveArenaSpawns(Player $player): void
    {
        $playerName = $player->getName();
        $arenaName = SessionManager::getSession($player)->getDevelopedArena();

        if ($arenaName !== null && isset(self::$spawnsPoint[$playerName])) {
            $arena = Loader::getLoader()->getArenaManager()->getArena($arenaName);
            if ($arena !== null) {
               // $arena->setSpawns(self::$spawnsPoint[$playerName]);
                Loader::getLoader()->getArenaManager()->updateArena($arena);
                $player->sendMessage(TextFormat::GREEN . "Spawns for arena $arenaName saved.");
                unset(self::$spawnsPoint[$playerName]);
            }
        }
    }


}