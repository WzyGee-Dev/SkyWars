<?php

namespace com\Skywars\arena\data;


use com\Skywars\arena\Arena;
use com\Skywars\arena\data\interface\IArenaRepository;
use com\Skywars\extensions\WorldBackup;
use com\Skywars\Loader;
use pocketmine\block\Ladder;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class ArenaRepository implements IArenaRepository
{

    public function createArena(string $id, string $name): void {
        Server::getInstance()->getWorldManager()->loadWorld($id, true);
        $arenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/" . $id . ".json";
        if (!file_exists($arenaPath)) {
            $config = new Config($arenaPath, Config::JSON);
            $config->set("id", $id);
            $config->set("name", $name);
            $config->save();

            WorldBackup::createBackup($id, $name);
        }
    }

    public function arenaExists(string $id): bool {
        $arenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/" . $id . ".json";
        return file_exists($arenaPath);
    }

    public function setSpawnPoint(string $id, int $slot, Position $position): void {
        $arenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/" . $id . ".json";
        $config = new Config($arenaPath, Config::JSON);
        $spawnPoints = $config->get("spawn_points", []);
        $spawnPoints[$slot] = [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "level" => $id
        ];
        $config->set("spawn_points", $spawnPoints);
        $config->save();
    }

    public function copyMainArenaData(string $id): void {
        $mainArenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/.json";
        $newArenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/" . $id . ".json";

        if (file_exists($mainArenaPath) && !file_exists($newArenaPath)) {
            copy($mainArenaPath, $newArenaPath);
        }
    }


    public function setLobbyPoint(string $id, Position $position): void {
        $arenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/" . $id . ".json";
        $config = new Config($arenaPath, Config::JSON);
        $config->set("lobby_point", [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "level" => $id
        ]);
        $config->save();
    }

    public function loadArenaData(string $id): array {
        $arenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/" . $id . ".json";
        if (file_exists($arenaPath)) {
            $config = new Config($arenaPath, Config::JSON);
            return $config->getAll();
        }
        return [];
    }

    public function setVoidRegion(string $id, Position $position): void {
        $arenaPath = \com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/" . $id . ".json";
        $config = new Config($arenaPath, Config::JSON);
        $config->set("void_region", [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "level" => $id
        ]);
        $config->save();
    }

    public function generateUniqueId(string $name): string {
        return $name . "_" . substr(md5(uniqid(mt_rand(), true)), 0, 4);
    }

    public function deleteArena(string $id): void {
        Server::getInstance()->getWorldManager()->unloadWorld(Server::getInstance()->getWorldManager()->getWorldByName($id));
        WorldBackup::removeWorld($id);
        @unlink(Loader::getInstance()->getDataFolder() . "arenas/{$id}.yml");
    }
    private function removeArenaFromConfig(string $id): void {
        $config = new Config(\com\Skywars\Loader::getInstance()->getDataFolder() . "arenas.yml", Config::YAML);
        $arenas = $config->get("arenas", []);
        if (isset($arenas[$id])) {
            unset($arenas[$id]);
        }
        $config->set("arenas", $arenas);
        $config->save();
    }
}