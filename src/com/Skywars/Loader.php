<?php

namespace com\Skywars;


use com\Skywars\arena\ArenaManager;
use com\Skywars\arena\data\ArenaRepository;
use com\Skywars\arena\data\entity\ArenaEntity;
use com\Skywars\arena\sign\SignManager;
use com\Skywars\commands\EntityCommand;
use com\Skywars\commands\SignCommand;
use com\Skywars\commands\SwCommand;
use com\Skywars\entity\EntityManager;
use com\Skywars\task\UpdateSign;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class Loader extends PluginBase
{
    public static self $instance;
    public ArenaManager $arenaManager;
    private array $signPlayers = [];
    public SignManager $signManager;

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function onEnable(): void
    {
        self::$instance = $this;
        @mkdir($this->getDataFolder().'arenas/');
        $repository = new ArenaRepository();
        $this->arenaManager = new ArenaManager($repository);
        $this->arenaManager->loadWorlds();
        $this->getServer()->getCommandMap()->register('swcreate', new SwCommand());
        $this->getServer()->getCommandMap()->register('swsign', new SignCommand());
        $this->getServer()->getCommandMap()->register('swentity', new EntityCommand());
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        EntityFactory::getInstance()->register(ArenaEntity::class, function(World $world, CompoundTag $nbt): ArenaEntity {
            $skin = new Skin("Standard_Custom", str_repeat("\x00", 8192));
            return new ArenaEntity(EntityDataHelper::parseLocation($nbt, $world), $skin, $nbt);
        }, ['ArenaEntity']);
        EntityManager::loadEntities();
        if (!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        //tarea sign

        $this->signManager = new SignManager($this->getScheduler(), $this->arenaManager);
    }

    public function getArenaManager(): ArenaManager
    {
        return $this->arenaManager;
    }
    public function addSignPlayer(Player $player): void {
        $this->signPlayers[$player->getName()] = $player;
    }

    public function isSignPlayer(Player $player): bool {
        return isset($this->signPlayers[$player->getName()]);
    }

    public function removeSignPlayer(Player $player): void {
        unset($this->signPlayers[$player->getName()]);
    }

    public function getSignManager(): SignManager
    {
        return $this->signManager;
    }
}