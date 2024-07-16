<?php

namespace SkyWars\Arena\extends\event;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LoomStackRequestAction;
use pocketmine\player\Player;
use SkyWars\Arena\extends\ArenaSetup;
use SkyWars\Loader;
use SkyWars\Sessions\SessionManager;

class CreateEvent implements Listener
{
    public array $slot = [];
    public function __construct()
    {
        Loader::getLoader()->getServer()->getPluginManager()->registerEvents($this, Loader::getLoader());
    }


    public function onDrop(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if(SessionManager::getSession($player)->isDeveloped() && ($event->getItem()->getNamedTag()->getByte('SpawnSetter', 0) === 1)){
            $event->cancel();
        }

    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if(SessionManager::getSession($player)->isDeveloped() && ($event->getItem()->getNamedTag()->getByte('SpawnSetter', 0) === 1)){
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if(SessionManager::getSession($player)->isDeveloped() && ($event->getItem()->getNamedTag()->getByte('SpawnSetter', 0) === 1)){
            $arenaName = SessionManager::getSession($player)->getDevelopedArena();
            if($arenaName === null){
                $player->sendMessage('You are not in development mode for any arena');
                return;
            }
            $arena = Loader::getLoader()->getArenaManager()->getArena($arenaName);
            if($arena === null){
                $player->sendMessage('The arena'. $arenaName. 'doest not exist');
                return;
            }
            $slot = $this->getPlayerSlot($player);
            if($slot > $arena->getMaxplayer()){
                $player->sendMessage('maximo de slots');
                return;
            }
            $pos = $event->getBlock()->getPosition();
            ArenaSetup::registerSpawn($player, $pos);
            Loader::getLoader()->getArenaManager()->updateArena($arena);
            $this->increment($player);
            $event->cancel();
        }
    }

    private function getPlayerSlot(\pocketmine\player\Player $player): int
    {
       return $this->slot[$player->getName()] ?? 1;
    }
    public function increment(Player $player): void
    {
        $this->slot[$player->getName()] = $this->getPlayerSlot($player) + 1;
    }
}