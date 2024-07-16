<?php

namespace SkyWars\Sessions;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use SkyWars\Arena\Arena;
use SkyWars\Loader;
use SkyWars\Sessions\interface\AbstractSession;

class PlayerSession extends AbstractSession
{

    public array $developed = [];

    public function initializeDataFolder(Player $player): string
    {
        $uuid = $player->getUniqueId()->toString();

        $folderPath = Loader::getLoader()->getDataFolder(). 'sessions/'.$uuid;
        if(!file_exists($folderPath)){
            mkdir($folderPath, 0777, true);

            file_put_contents($folderPath . '/data.json', json_encode(['name' =>$player->getName(), 'uuid' => $uuid]));
        }
        return $folderPath;
    }


    public function joinDeveloped(string $name): void
    {
        $this->developed[$this->getPlayer()->getName()] = $name;
        $this->player->sendMessage(TextFormat::GREEN.'You are now in development mode for arena: '.$name);
        $this->player->getInventory()->clearAll();
        $this->player->getArmorInventory()->clearAll();

    }

    public function isDeveloped(): bool
    {
        return isset($this->developed[$this->getPlayer()->getName()]);
    }

    public function getDevelopedArena(): ?string
    {
        return $this->developed[$this->getPlayer()->getName()] ?? null;
    }

    public function getArenaByPlayer(): ?Arena
    {
        foreach (Loader::getLoader()->getArenaManager()->getAllArenas() as $arena) {
            if(in_array($this->player->getName(), array_map(fn($spawn) => $spawn->asVector3()->__toString(), $arena->getPlayers()))){
                return $arena;
            }
       }
        return null;
    }

    public function saveData(array $data): void
    {
       file_put_contents($this->datafolder . '/data.json', json_encode($data));
    }

    public function loadData(): array
    {
      $filePath = $this->datafolder.'/data.json';
      if(file_exists($filePath)){
          return json_decode(file_get_contents($filePath), true);
      }
      return [];
    }
}