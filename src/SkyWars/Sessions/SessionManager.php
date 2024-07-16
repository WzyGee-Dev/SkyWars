<?php

namespace SkyWars\Sessions;

use pocketmine\player\Player;
use SkyWars\Loader;

class SessionManager
{

    public static array $sessions = [];

    public static function createSession(Player $player)
    {
        $uuid = $player->getUniqueId()->toString();
        if(!isset(self::$sessions[$uuid])){
            self::$sessions[$uuid] = new PlayerSession($player);
            self::log('Session createds for player '. $player->getName());
        }
        return self::$sessions[$uuid];
    }
    public static function getSession(Player $player): ?PlayerSession
    {
        $uuid = $player->getUniqueId()->toString();
        return self::$sessions[$uuid] ?? null;
    }

    public static function removeSession(Player $player): void
    {
        $uuid = $player->getUniqueId()->toString();
        if(isset(self::$sessions[$uuid])){
            unset(self::$sessions[$uuid]);
            self::log('Session remove for player '. $player->getName());
        }

    }

    public static function log($message): void
    {
        $logFile = Loader::getLoader()->getDataFolder().'session.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' '. $message .PHP_EOL, FILE_APPEND);
    }
}