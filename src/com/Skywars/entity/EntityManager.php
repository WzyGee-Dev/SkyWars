<?php

namespace com\Skywars\entity;

use com\Skywars\arena\data\ArenaMenu;
use com\Skywars\arena\data\entity\ArenaEntity;
use com\Skywars\Loader;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\BinaryDataException;
use pocketmine\world\Position;

class EntityManager
{

    private static array $entities = [];

    public static function addEntity(Location $location, string $entityId): void {
        $nbt = CompoundTag::create();
        $entity = new ArenaEntity($location,self::getDefaultSkin(), $nbt);
          //  $entity->setNameTag($entityId);
            $entity->setNameTagVisible(true);
            $entity->setNameTagAlwaysVisible(true);

            $entity->spawnToAll();
            self::$entities[$entity->getId()] = $entityId;
            self::saveEntities();
    }

    public static function removeEntity(Player $player): void {
        foreach ($player->getWorld()->getEntities() as $entity) {
            if ($entity instanceof ArenaEntity && $entity->getPosition()->distance($player->getPosition()) < 5) {
                $entity->flagForDespawn();
                unset(self::$entities[$entity->getId()]);
                $player->sendMessage("Entity removed.");
                self::saveEntities();
                return;
            }
        }
        $player->sendMessage("No entity found within 5 blocks.");
    }

    public static function handleEntityTouch(Player $player, Entity $entity): void {
        if (isset(self::$entities[$entity->getId()])) {
          ArenaMenu::openMenu($player);
        }
    }

    public static function saveEntities(): void {
        $data = [];
        foreach (self::$entities as $id => $entityId) {
            $entity = Server::getInstance()->getWorldManager()->findEntity($id);
            if ($entity !== null) {
                $data[] = [
                    "location" => [
                        "x" => $entity->getLocation()->getX(),
                        "y" => $entity->getLocation()->getY(),
                        "z" => $entity->getLocation()->getZ(),
                        "level" => $entity->getWorld()->getFolderName(),
                        "yaw" => $entity->getLocation()->getYaw(),
                        "pitch" => $entity->getLocation()->getPitch()
                    ],
                    "entityId" => $entityId
                ];
            }
        }
        file_put_contents(Loader::getInstance()->getDataFolder() . "entities.json", json_encode($data));
    }

    public static function loadEntities(): void {
        if (!file_exists(Loader::getInstance()->getDataFolder() . "entities.json")) {
            return;
        }

        $data = json_decode(file_get_contents(Loader::getInstance()->getDataFolder() . "entities.json"), true);
        foreach ($data as $entityData) {
            Server::getInstance()->getWorldManager()->loadWorld($entityData["location"]["level"]);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($entityData["location"]["level"]);
            if ($world !== null) {
                $location = new Location(
                    $entityData["location"]["x"],
                    $entityData["location"]["y"],
                    $entityData["location"]["z"],
                    $world,
                    $entityData["location"]["yaw"],
                    $entityData["location"]["pitch"]
                );
                self::addEntity($location , $entityData["entityId"]);
            }
        }
    }

    public static function getEntities(): array
    {
        return self::$entities;
    }

    private static function getDefaultSkin(): Skin {
        $path = Loader::getInstance()->getDataFolder() . "default_skin.png";
        if (!file_exists($path)) {
            throw new \RuntimeException("Default skin not found");
        }

        try {
            $img = @imagecreatefrompng($path);
            if ($img === false) {
                throw new \RuntimeException("Failed to load default skin");
            }

            $bytes = "";
            $size = getimagesize($path);
            for ($y = 0; $y < $size[1]; $y++) {
                for ($x = 0; $x < $size[0]; $x++) {
                    $rgba = imagecolorat($img, $x, $y);
                    $a = (~((($rgba & 0x7F000000) >> 24) << 1)) & 0xFF;
                    $bytes .= chr(($rgba >> 16) & 0xFF) . chr(($rgba >> 8) & 0xFF) . chr($rgba & 0xFF) . chr($a);
                }
            }
            imagedestroy($img);
        } catch (BinaryDataException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return new Skin("Standard_Custom", $bytes);
    }
}