<?php

namespace com\Skywars\commands;

use com\Skywars\Loader;
use com\Skywars\sessions\SessionManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SwCommand extends Command
{

    public function __construct() {
        parent::__construct("swcreate", "Create a SkyWars arena", "/swcreate {arena}", ["swcreate"]);
        $this->setPermission("skywars.command.swcreate");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return false;
        }

        if (count($args) < 1) {
            $sender->sendMessage("Usage: /swcreate {arena}");
            return false;
        }

        $arenaName = $args[0];
        $arena = Loader::getInstance()->getArenaManager()->createArena($arenaName);
        $session = SessionManager::getSession($sender);
        if ($session !== null) {
            $session->setArenaId($arena->getId());
            $session->setDeveloperMode(true);
            $this->sendDeveloperCommands($sender);
            $sender->sendMessage("Arena '$arenaName' created successfully with ID '{$arena->getId()}'.");
        }
        return true;
    }


    private function sendDeveloperCommands(Player $player): void {
        $commands = [
            TextFormat::GREEN. "setspawn {int} - Set a spawn point",
            TextFormat::GREEN. "setlobby - Set the lobby point",
            TextFormat::GREEN. "setvoid - Set the void region",
            TextFormat::GREEN. "save - Save all settings and exit developer mode"
        ];

        $player->sendMessage("Developer Mode Commands:");
        foreach ($commands as $command) {
            $player->sendMessage($command);
        }
    }
}