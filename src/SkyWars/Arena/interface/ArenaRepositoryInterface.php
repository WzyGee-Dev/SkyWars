<?php

namespace SkyWars\Arena\interface;

use SkyWars\Arena\Arena;

interface ArenaRepositoryInterface
{
    public function save(Arena $arena): void;
    public function delete(Arena $arena): void;
    public function findAll(): array;
    public function findByName(string $name): ?Arena;
}