<?php

namespace SkyWars;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use SkyWars\Sessions\interface\SessionInterface;
use SkyWars\Sessions\SessionManager;

class EventListener implements Listener
{


    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
       $session = SessionManager::createSession($player);
        Loader::getLoader()->getServer()->getLogger()->info('palyer' . $player->getName().'at'.$session->getDatafolder());
    }



    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $session = SessionManager::createSession($player);
        $arena = $session->getArenaByPlayer();
        if($arena !== null){
            Loader::getLoader()->getArenaManager()->removeFromArena($player, $arena);
        }
    }
}