<?php

namespace SkyWars\Arena;

use AllowDynamicProperties;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Arena
{
    public string $name;
    /**
     * @var array|int
     */
    public array|int $spawns;
    /**
     * @var mixed|string
     */
    public mixed $team;
     /**
      * @var int
      */
     private mixed $maxplayer;
    public array $players = [];

    public const int STATUS_WAITTING = 0;
    public const int STATUS_PLAYING = 1;
    public const int STATUS_FINISHED = 2;
    public int $status;


    /**
     * @param string $name
     */
    public function __construct(string $name, $spawn = [], $team = 'solo', $maxplayer = 12)
    {
        $this->name = $name;
        $this->spawns = $spawn;
        $this->team = $team;
        $this->maxplayer = $maxplayer;
        $this->status = self::STATUS_WAITTING;
    }
    public static function fromArray(array $data): Arena
    {
        $spawns = array_map(fn($pos) => self::arrayToVector($pos), $data['spawns']);
        return new self($data['name'], $spawns, $data['mode'], $data['maxplayers'] ?? 12);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'spawns' => array_map(fn($pos) => $this->vectorToArray($pos), $this->spawns),
            'mode' => $this->team,
            'maxplayers' => $this->maxplayer
        ];
    }

    private function vectorToArray(Vector3 $vector): array {
        return [$vector->getX(), $vector->getY(), $vector->getZ()];
    }
    private static function arrayToVector(array $array): Vector3 {
        return new Vector3($array[0], $array[1], $array[2]);
    }
    public function getName(): string
    {
        return $this->name;
    }

     public function setMaxplayer(int $maxplayer): void
     {
         $this->maxplayer = $maxplayer;
     }

     public function getMaxplayer(): int
     {
         return $this->maxplayer;
     }

     public function getSpawns(): array|int
     {
         return $this->spawns;
     }

     public function addSpawn(Vector3 $vector3, int $slot): void
     {
         $this->spawns[$slot] = $vector3;
     }

     public function getTeam(): mixed
     {
         return $this->team;
     }

     public function setTeam(mixed $team): void
     {
         $this->team = $team;
     }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function isFull(): bool
    {
        return count($this->getPlayers()) >= $this->getMaxplayer();
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function addOcuppedSlot(Player $player, int $slot): void
    {
      $this->players[$slot] = $player->getName();
    }

    public function removePlayerSpawn(Vector3 $vector3): void
    {
        $key = array_search($vector3, $this->players, true);
        if($key !== null){
            unset($this->players[$key]);
            $this->players = array_values($this->players);
        }
    }
}