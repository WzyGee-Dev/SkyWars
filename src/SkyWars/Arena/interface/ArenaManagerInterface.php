<?php

namespace SkyWars\Arena\interface;

use SkyWars\Arena\Arena;

interface ArenaManagerInterface
{
    public function createArena(string $name): Arena;
    public function getArena(string $name): ?Arena;
    public function getAllArenas(): array;
    public function deleteArena(string $name): void;
}