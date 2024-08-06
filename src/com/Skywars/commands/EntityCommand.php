<?php

namespace com\Skywars\commands;

use com\Skywars\entity\EntityManager;
use com\Skywars\Loader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class EntityCommand extends Command
{

    public function __construct() {
        parent::__construct("swentity", "Create a entity", "/entity <add|remove>", ["entity"]);
        $this->setPermission("skywars.command.swentity");
    }



    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return false;
        }

        if (count($args) < 1) {
            $sender->sendMessage("Usage: /entity <add|remove>");
            return false;
        }

        $action = strtolower($args[0]);

        switch ($action) {
            case "add":
               EntityManager::addEntity($sender->getLocation(), 'ArenaId');
                $sender->sendMessage("Entity added.");
                break;
            case "remove":
           //  EntityManager::removeEntity($sender);
               $arena = Loader::getInstance()->getArenaManager()->getArenaByPlayer($sender);
                if($arena !== null){
                    $arena->removePlayer($sender);
                } else {
                    $sender->sendMessage('sin mundo');
                }
                break;
            default:
                $sender->sendMessage("Usage: /entity <add|remove>");
                break;
        }

        return true;
    }
}