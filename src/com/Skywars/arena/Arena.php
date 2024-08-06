<?php

namespace com\Skywars\arena;

use com\Skywars\arena\data\ArenaStatus;
use com\Skywars\Loader;
use com\Skywars\task\GameTask;
use pocketmine\block\BaseSign;
use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\world\Position;
use const http\Client\Curl\Versions\ARES;

class Arena
{
    private string $id;
    private string $name;
    private array $spawnPoints;
    public ?Position $lobbyPoint;
    public ?Position $voidRegion;
    public string $status;
    public array $signs;
    private array $players;
    public ?TaskHandler $taskHandler;
    private int $countdown = 20;
    private int $gameTime = 300;

    private int $endTime = 10;

    public function __construct(string $id, string $name) {
        $this->id = $id;
        $this->name = $name;
        $this->spawnPoints = [];
        $this->lobbyPoint = null;
        $this->voidRegion = null;
        $this->status = ArenaStatus::WAITING;
        $this->signs = [];
        $this->players = [];
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSpawnPoints(): array {
        return $this->spawnPoints;
    }

    public function setSpawnPoint(int $slot, Position $position): void {
        $this->spawnPoints[$slot] = $position;
    }

    public function setSpawnPoints(array $spawns): void
    {
        $this->spawnPoints = $spawns;
    }
    public function setLobbyPoint(Position $position): void {
        $this->lobbyPoint = $position;
    }

    public function setVoidRegion(Position $position): void {
        $this->voidRegion = $position;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }


    public function serialize(): array {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "spawn_points" => array_map(function(Position $position) {
                return [
                    "x" => $position->getX(),
                    "y" => $position->getY(),
                    "z" => $position->getZ(),
                    "level" => $this->id
                ];
            }, $this->spawnPoints),
            "lobby_point" => $this->lobbyPoint !== null ? [
                "x" => $this->lobbyPoint->getX(),
                "y" => $this->lobbyPoint->getY(),
                "z" => $this->lobbyPoint->getZ(),
                "level" => $this->id
            ] : null,
            "void_region" => $this->voidRegion !== null ? [
                "x" => $this->voidRegion->getX(),
                "y" => $this->voidRegion->getY(),
                "z" => $this->voidRegion->getZ(),
                "level" => $this->id
            ] : null,
        ];
    }

    public static function deserialize(array $data): Arena {
        $arena = new self($data["id"], $data["name"]);
        $arena->setStatus(ArenaStatus::WAITING);
        foreach ($data["spawn_points"] ?? [] as $spawnData) {
            Server::getInstance()->getWorldManager()->loadWorld($spawnData["level"]);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($spawnData["level"]);
            if ($world !== null) {
                $position = new Position($spawnData["x"], $spawnData["y"], $spawnData["z"], $world);
                $arena->setSpawnPoint(count($arena->getSpawnPoints()), $position);
            }
        }
        if (isset($data["lobby_point"])) {
            Server::getInstance()->getWorldManager()->loadWorld($data["lobby_point"]["level"]);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($data["lobby_point"]["level"]);
            if ($world !== null) {
                $position = new Position($data["lobby_point"]["x"], $data["lobby_point"]["y"], $data["lobby_point"]["z"], $world);
                $arena->setLobbyPoint($position);
                Server::getInstance()->getLogger()->info('se carga spawns');
            }
        }
        if (isset($data["void_region"])) {
            Server::getInstance()->getWorldManager()->loadWorld($data["void_region"]["level"]);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($data["void_region"]["level"]);
            if ($world !== null) {
                $position = new Position($data["void_region"]["x"], $data["void_region"]["y"], $data["void_region"]["z"], $world);
                $arena->setVoidRegion($position);
                Server::getInstance()->getLogger()->info('se carga el void');
            }
        }
        return $arena;
    }

 
    public function addPlayer(Player $player): void {
        $this->players[$player->getName()] = $player;
        $player->sendMessage("You have joined the arena '{$this->getId()}'.");
        Loader::getInstance()->getSignManager()->updateSigns();
        $this->teleportPlayerToLobby($player);
        Loader::getInstance()->getLogger()->info('PALYERS '. count($this->players));
        
        if(count($this->getPlayers()) >= 2 && $this->getStatus() === ArenaStatus::WAITING)
        {
            $this->start();
        } else {
            $this->setStatus(ArenaStatus::WAITING);
        }
    }

    public function removePlayer(Player $player): void {
        if(isset($this->players[$player->getName()])) {
            unset($this->players[$player->getName()]);
            $player->sendMessage("You have left the arena '{$this->name}'.");
        }

        if (count($this->getPlayers()) < 2 && ($this->getStatus() === ArenaStatus::IN_PROGRESS)) {
            $this->handleEndGame();
        }
    }

    public function teleportPlayerToLobby(Player $player): void {
        Server::getInstance()->getWorldManager()->loadWorld($this->id);
        Server::getInstance()->getLogger()->info('load'. $this->getId());
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->id);
        if ($world !== null) {
        if ($this->lobbyPoint !== null) {
            $position = new Position($this->lobbyPoint->getX(), $this->lobbyPoint->getY(), $this->lobbyPoint->getZ(), $world);
            $player->teleport($position);
        } else {
            $player->sendMessage("The lobby point for this arena is not set.");
        }
        } else {
            $player->sendMessage("The world for this arena is not set.");
        }
    }

    public function getPlayers(): array {
        return $this->players;
    }

    private function start(): void
    {
        $this->setStatus(ArenaStatus::STARTING);
        $this->taskHandler = Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
    }
    
    
    public function tick(): void
    {
        switch ($this->getStatus()){
            case ArenaStatus::STARTING:
                $this->handleStarting();
            break;
            case ArenaStatus::IN_PROGRESS:
                $this->gameTick();
                break;
            case ArenaStatus::RELOADING:
                $this->reloadTick();
                break;
            default:
                break;
        }
    }

    public function handleEndGame(): void {
        $remainingPlayers = array_values($this->players);
        if (count($remainingPlayers) === 1) {
            $winner = $remainingPlayers[0];
            $this->setStatus(ArenaStatus::RELOADING);
            $this->endGame();
        }
    }
    private function handleStarting(): void {
        if ($this->countdown <= 0) {
            $this->startGame();
        } else {
            foreach ($this->players as $player) {
                $player->sendMessage("The game starts in {$this->countdown} seconds.");
            }
            $this->countdown--;
        }
    }

    public function gameTick(): void {
        if($this->gameTime <= 0){
            $this->endGame();
        } else {
            $this->handleEndGame();
            $this->gameTime--;
        }

        foreach ($this->getPlayers() as $player) {
            if($this->isPlayerInVoid($player)){
                $this->handlePlayerDeath($player, null);
            }
        }
    }

    private function isPlayerInVoid(Player $player): bool
    {
        $voidRegion = $this->getVoidRegion();
        return $player->getPosition()->getY() < $voidRegion->getY();
    }

    public function startGame(): void
    {
        $this->setStatus(ArenaStatus::IN_PROGRESS);

        foreach ($this->players as $player) {
            $spawnPoint = $this->getSpawnPoints()[array_rand($this->getSpawnPoints())];
            $player->teleport($spawnPoint);
        }
    }

    public function endGame(): void {
        foreach ($this->players as $player) {
                $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
        }
        Server::getInstance()->getLogger()->info('carga endgame');
        Loader::getInstance()->getArenaManager()->createArenaClone($this);
        Loader::getInstance()->getArenaManager()->deleteArena($this->getId());

    }

    private function reloadTick(): void
    {
        //in developed
    }

    public function getLobbyPoint(): ?Position
    {
        return $this->lobbyPoint;
    }

    public function getVoidRegion(): ?Position
    {
        return $this->voidRegion;
    }


    public function handlePlayerDeath(Player $player, ?Player $damager): void {
        $this->removePlayer($player);
        $player->sendMessage("You have died.");
        if ($damager !== null) {
            $damager->sendMessage("You killed " . $player->getName() . ".");
        }
        if ($this->lobbyPoint !== null) {
            $player->teleport($this->lobbyPoint);
        }
        if (count($this->players) <= 1) {
            $this->handleEndGame();
        }
    }

    public function broadcastMessage(string $message): void
    {
        foreach ($this->players as $player) {
            $player->sendMessage($message);
        }
    }
}