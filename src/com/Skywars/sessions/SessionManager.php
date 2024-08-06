<?php

namespace com\Skywars\sessions;

use pocketmine\player\Player;

class SessionManager
{

    private static array $sessions = [];

    public static function createSession(Player $player): void {
        self::$sessions[$player->getName()] = new Session($player);
    }

    public static function getSession(Player $player): ?Session {
        return self::$sessions[$player->getName()] ?? null;
    }

    public function removeSession(Player $player): void {
        unset(self::$sessions[$player->getName()]);
    }
}