<?php

namespace com\Skywars\arena\data\interface;

use pocketmine\world\Position;

interface IArenaRepository
{

    public function createArena(string $id, string $name):void;

    public function setSpawnPoint(string $id, int $slot, Position $position): void;

    public function arenaExists(string $id): bool;

    public function copyMainArenaData(string $id): void;

    public function generateUniqueId(string $name): string;

    public function setLobbyPoint(string $id, Position $position): void;
    public function loadArenaData(string $id): array;

    public function setVoidRegion(string $id, Position $position): void;


}