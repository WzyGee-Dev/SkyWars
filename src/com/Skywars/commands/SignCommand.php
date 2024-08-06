<?php

namespace com\Skywars\commands;

use com\Skywars\Loader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;

class SignCommand extends Command
{


    public function __construct() {
        parent::__construct("swsign", "Create a SkyWars arena", "/swcreate {arena}", ["join"]);
        $this->setPermission("skywars.command.swsign");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
        $sender->sendMessage("This command can only be used in-game.");
        return false;
    }

        if ($sender->hasPermission("skywars.command.setsign")) {
            $sender->sendMessage("You can only use this command once.");
            return false;
        }

   //   Loader::getInstance()->addSignPlayer($sender);
        $arena = Loader::getInstance()->getArenaManager()->getRandomAvailableArena();
        if($arena !== null){
            $arena->addPlayer($sender);
        }
        $sender->sendMessage("You can now register a sign by right-clicking on it.");
        return true;
    }

}