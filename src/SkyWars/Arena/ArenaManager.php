<?php

namespace SkyWars\Arena;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use SkyWars\Arena\exception\ArenaNotFoundException;
use SkyWars\Arena\interface\ArenaManagerInterface;
use SkyWars\Arena\interface\ArenaRepositoryInterface;
use SkyWars\Arena\task\ArenaScheduler;
use SkyWars\Loader;
use SkyWars\Sessions\SessionManager;

class ArenaManager implements ArenaManagerInterface
{

    public ArenaRepositoryInterface $repository;

    public array $schedulers = [];
    private array $arenas = [];

    public function __construct(ArenaRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function createArena(string $name): Arena
    {
        $this->arenas[$name] = new Arena($name);
        $this->repository->save($this->arenas[$name]);
        return $this->arenas[$name];
    }

    public function getArena(string $name): ?Arena
    {
        return $this->arenas[$name] ?? null;
    }

    public function getAllArenas(): array
    {
        return array_values($this->arenas);
    }

    /**
     * @throws ArenaNotFoundException
     */
    public function deleteArena(string $name) :void
    {
        $arena = $this->repository->findByName($name);
        if ($arena) {
            $this->repository->delete($arena);
        } else {
            throw new ArenaNotFoundException("Arena with name $name not found.");
        }
    }


    public function updateArena(Arena $arena): void
    {
        $this->repository->save($arena);
    }

    public function load(): void
    {
        $this->repository->load();
    }


    public function teleportRandomArena(Player $player): bool
    {

        $availableArenas = array_filter($this->arenas, fn($arena) => !$arena->isFull() && $arena->getStatus() === Arena::STATUS_WAITTING);
        if(empty($availableArenas)){
            $player->sendMessage('no available arenas found');
            return false;
        }
        $randomArena = $availableArenas[array_rand($availableArenas)];
        $spawn = $this->getRandomAvailableSpawn($randomArena);
        if($spawn === null){
            $player->sendMessage('no available spawns in the selected arena');
            return false;
        }
        if($randomArena instanceof Arena) {

        }
        if(!isset($this->schedulers[$randomArena->getName()])){
            $scheduler = new ArenaScheduler($randomArena);
            $this->schedulers[$randomArena->getName()] = $scheduler;
            Loader::getLoader()->getScheduler()->scheduleRepeatingTask($scheduler, 20);
        }
        return true;
    }

    private function getRandomAvailableSpawn(Arena $randomArena): ?Vector3
    {
        $availableSpawns = array_filter($randomArena->getSpawns(), fn($spawn) => !in_array($spawn, $randomArena->getPlayers(), true));
        return empty($availableSpawns) ? null : $availableSpawns[array_rand($availableSpawns)];
    }
 public function quitArena(Player $player): void
 {
     foreach ($this->arenas as $arena) {
         if($this->isPlayerArena($player, $arena)){
             $this->removeFromArena($player, $arena);
             $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
             return;
         }
     }
 }
    public function isPlayerArena(Player $player, Arena $arena): bool
    {
        foreach ($arena->getPlayers() as $spawn) {
            if($spawn->equals($player->getPosition())){
                return true;
            }
        }
        return false;
    }

    public function removeFromArena(Player $player, Arena $arena): void
    {
       $playerSpawn = $player->getPosition();
       $arena->removePlayerSpawn($playerSpawn);
            $player->sendMessage(TextFormat::RED.'You have left the arena '. $arena->getName());
        if(count($arena->getPlayers()) === 0){
            if(isset($this->schedulers[$arena->getName()])){
                $this->schedulers[$arena->getName()]->cancel();
                unset($this->schedulers[$arena->getName()]);
                $arena->setStatus(Arena::STATUS_WAITTING);
            }
        }
    }

}