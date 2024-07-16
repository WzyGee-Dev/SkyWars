<?php

namespace SkyWars\Commands\default;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SkyWars\Arena\extends\ArenaForm;
use SkyWars\Arena\extends\ArenaSetup;
use SkyWars\Loader;
use SkyWars\Sessions\SessionManager;

class SkyWarsCommand extends Command
{

    public function __construct()
    {
        parent::__construct('sw', 'SkyWars command', 'Use: /sw help', ['sw', 'swr', 'skywars']);
        $this->setPermission('skywars.command');
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED. 'Run this command in-game');
        }
       $subCommand = $args[0];
        switch ($subCommand){
            case 'create':
                Loader::getLoader()->getArenaManager()->createArena($args[1]);
                SessionManager::getSession($sender)->joinDeveloped($args[1]);
                break;
            case 'dev':
                if(SessionManager::getSession($sender)->isDeveloped()){
                    ArenaForm::developed($sender);
                }
                break;
            case 'join':
               Loader::getLoader()->getArenaManager()->teleportRandomArena($sender);
                break;
            case 'quit':
                Loader::getLoader()->getArenaManager()->quitArena($sender);
                break;
        }

    }
}