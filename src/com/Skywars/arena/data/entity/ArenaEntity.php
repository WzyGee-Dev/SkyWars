<?php

namespace com\Skywars\arena\data\entity;

use com\Skywars\arena\data\ArenaMenu;
use com\Skywars\Loader;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class ArenaEntity extends Human
{

    public static function getNetworkTypeId(): string {
        return "minecraft:human";
    }

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null) {
        parent::__construct($location, $skin, $nbt);
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new class($this) extends Task
        {
            public ArenaEntity $arenaEntity;

            public function __construct(ArenaEntity $entity)
           {
               $this->arenaEntity = $entity;
           }
           public function onRun(): void
           {
               $this->arenaEntity->updateNameTag();
           }
        }, 20);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.8, 0.6);
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        ArenaMenu::openMenu($player);
     return true;
    }


    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->setMotion(new Vector3(0, 0, 0));
        return parent::entityBaseTick($tickDiff);
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        $this->setHealth($this->getMaxHealth());
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel();
    }

    public function updateNameTag(): void {
        $totalPlayers = Loader::getInstance()->getArenaManager()->getTotalPlayers();
        $this->setNameTag("Arena Selector\nPlayers: $totalPlayers");
    }
}