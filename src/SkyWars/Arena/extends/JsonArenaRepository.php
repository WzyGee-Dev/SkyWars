<?php

namespace SkyWars\Arena\extends;

use SkyWars\Arena\Arena;
use SkyWars\Arena\interface\ArenaRepositoryInterface;

class JsonArenaRepository implements ArenaRepositoryInterface
{


     public string $filePath;
     public array $arenas = [];

     public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->load();
    }

    public function load(): void
    {
        if(!file_exists($this->filePath)){
            return;
        }
        $data = json_decode(file_get_contents($this->filePath), true);
        foreach ($data as $datum) {
            $this->arenas[$datum['name']] = Arena::fromArray($datum);
        }
    }

    public function save(Arena $arena): void {
        $this->arenas[$arena->getName()] = $arena;
        $this->persist();
    }

    public function delete(Arena $arena): void {
        unset($this->arenas[$arena->getName()]);
        $this->persist();
    }

    public function findAll(): array {
        return array_values($this->arenas);
    }

    public function findByName(string $name): ?Arena {
        return $this->arenas[$name] ?? null;
    }

    private function persist(): void {
        $data = [];
        foreach ($this->arenas as $arena) {
            $data[] = $arena->toArray();
        }
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}