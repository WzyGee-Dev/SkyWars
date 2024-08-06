<?php

namespace com\Skywars\arena;

use com\Skywars\arena\data\ArenaStatus;
use com\Skywars\arena\data\interface\IArenaRepository;
use com\Skywars\Loader;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class ArenaManager
{

    private IArenaRepository $repository;
    private array $arenas = [];

    public function __construct(IArenaRepository $repository) {
        $this->repository = $repository;
        $this->arenas = [];
        $this->loadArenas();
    }

    public function createArena(string $name): Arena {
        $id = $this->repository->generateUniqueId($name);
        $this->repository->createArena($id, $name);
        $this->repository->copyMainArenaData($id);
        $arena = new Arena($id, $name);
        $this->arenas[$id] = $arena;
        $this->saveArena($arena);
        $this->saveArenas();
        return $arena;
    }

    public function arenaExists(string $id): bool {
        return $this->repository->arenaExists($id);
    }

    public function setSpawnPoint(Arena $arena, int $slot, Position $position): void {
        $arena->setSpawnPoint($slot, $position);
        $this->repository->setSpawnPoint($arena->getId(), $slot, $position);
    }

    public function setLobbyPoint(Arena $arena, Position $position): void {
        $arena->setLobbyPoint($position);
        $this->repository->setLobbyPoint($arena->getId(), $position);
    }

    public function getArena(string $id): ?Arena {
        if (isset($this->arenas[$id])) {
            return $this->arenas[$id];
        }

        if ($this->repository->arenaExists($id)) {
            $arenaData = $this->repository->loadArenaData($id);
            $arena = new Arena($id, $arenaData['name']);
            $this->arenas[$id] = $arena;
            return $arena;
        }

        return null;
    }

    public function getArenas(): array
    {
        return $this->arenas;
    }

    public function setVoidRegion(Arena $arena, Position $position): void {
        $arena->setVoidRegion($position);
        $this->repository->setVoidRegion($arena->getId(), $position);
    }

    public function getRandomArena(): ?Arena {
        $arenaIds = array_keys($this->arenas);
        if (empty($arenaIds)) {
            return null;
        }

        $randomId = $arenaIds[array_rand($arenaIds)];
        return $this->getArena($randomId);
    }


    public function loadArenas(): void {
        $config = new Config(Loader::getInstance()->getDataFolder() . "arenas/arenas.yml", Config::YAML);
        $arenas = $config->get("arenas", []);
        foreach ($arenas as $id => $data) {
            $arenaConfig = new Config(Loader::getInstance()->getDataFolder() . "arenas/{$id}.json", Config::JSON);
            $arenaData = $arenaConfig->getAll();
            $arena = Arena::deserialize($arenaData);
            $this->arenas[$id] = $arena;

        }
    }

    public function saveArenas(): void {
        $config = new Config(\com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/arenas.yml", Config::YAML);
        $arenas = [];
        foreach ($this->arenas as $id => $arena) {
            $arenas[$id] = ["id" => $arena->getId(), "name" => $arena->getName()];
        }
        $config->set("arenas", $arenas);
        $config->save();
        Server::getInstance()->getLogger()->info('se carga  el saveArenas');
    }

    private function saveArena(Arena $arena): void {
        $config = new Config(\com\Skywars\Loader::getInstance()->getDataFolder() . "arenas/{$arena->getId()}.json", Config::JSON);
        $config->setAll($arena->serialize());
        $config->save();
        $this->saveArenas();
    }

    public function getAvailableArena(): ?Arena {
        foreach ($this->arenas as $arena) {
            if ($arena->getStatus() === ArenaStatus::WAITING) {
                return $arena;
            }
        }
        return null;
    }


    public function loadWorlds(): void {
        $worldManager = Server::getInstance()->getWorldManager();
        foreach ($this->arenas as $arena) {
                $worldManager->loadWorld($arena->getId());
                Server::getInstance()->getLogger()->info('se carga las ids arena'. $arena->getId());
        }
    }

    public function getTotalPlayers(): int {
        $totalPlayers = 0;
        foreach ($this->arenas as $arena) {
            $totalPlayers += count($arena->getPlayers());
        }
        return $totalPlayers;
    }

    public function getRandomAvailableArena(): ?Arena {
        $availableArenas = array_filter($this->arenas, function (Arena $arena) {
            return $arena->getStatus() === ArenaStatus::WAITING || $arena->getStatus() === ArenaStatus::STARTING;
        });

        if (empty($availableArenas)) {
            return null;
        }

        return $availableArenas[array_rand($availableArenas)];
    }

    public function getArenaByPlayer(Player $player): ?Arena {
        foreach ($this->arenas as $arena) {
            if($arena instanceof Arena) {
                if (array_key_exists($player->getName(), $arena->getPlayers())) {
                    return $arena;
                }
            }
        }
        return null;
    }


    public function createArenaClone(Arena $originalArena): Arena {
        $newArena = $this->createArena($originalArena->getName());
        $newArena->setSpawnPoints($originalArena->getSpawnPoints());
        $newArena->setLobbyPoint($originalArena->getLobbyPoint());
        $newArena->setVoidRegion($originalArena->getVoidRegion());
        $this->saveArena($newArena);
        return $newArena;
    }

    public function deleteArena(string $id): void {
        if (isset($this->arenas[$id])) {
            unset($this->arenas[$id]);
            $this->repository->deleteArena($id);
        }
    }
}