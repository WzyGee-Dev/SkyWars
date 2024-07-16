<?php

namespace SkyWars;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SkyWars\Arena\ArenaManager;
use SkyWars\Arena\extends\event\CreateEvent;
use SkyWars\Arena\extends\JsonArenaRepository;
use SkyWars\Commands\default\SkyWarsCommand;

class Loader extends PluginBase
{
    private static Loader $loader;
    private ArenaManager $arenaManager;

    public static function getLoader(): Loader
    {
        return self::$loader;
    }

    public function onEnable(): void
{
    self::$loader = $this;
    $this->getServer()->getCommandMap()->register('sw', new SkyWarsCommand());
    @mkdir($this->getDataFolder(). 'arenas');
    new Config($this->getDataFolder(). 'arenas/arenas.json', Config::JSON);
    $repository = new JsonArenaRepository($this->getDataFolder().'arenas/arenas.json');
    $this->arenaManager = new ArenaManager($repository);
    $this->arenaManager->load();
    $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    new CreateEvent();

    $this->getServer()->getLogger()->info('Skywars plugin enabled');

}

    public function getArenaManager(): ArenaManager
    {
        return $this->arenaManager;
    }
}