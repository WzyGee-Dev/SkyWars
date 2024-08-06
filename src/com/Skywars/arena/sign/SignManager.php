<?php

namespace com\Skywars\arena\sign;

use com\Skywars\arena\ArenaManager;
use com\Skywars\Loader;
use com\Skywars\task\UpdateSign;
use pocketmine\block\BaseSign;
use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use pocketmine\world\Position;

class SignManager
{

    private TaskScheduler $scheduler;
    private ArenaManager $arenaManager;
    private array $signs;

    public function __construct(TaskScheduler $scheduler, ArenaManager $arenaManager) {
        $this->scheduler = $scheduler;
        $this->arenaManager = $arenaManager;
        $this->signs = [];

        $this->scheduler->scheduleRepeatingTask(new UpdateSign($this), 100); // Actualizar cada 5 segundos
        $this->loadSigns();
    }

    public function addSign(Position $position, string $arenaId): void {
        $this->signs[] = [
            "position" => $position,
            "arenaId" => $arenaId
        ];
        $this->updateSign($position, $arenaId);
        $this->saveSigns();
    }

    public function removeSign(Position $position): void {
        foreach ($this->signs as $index => $sign) {
            if ($sign["position"]->equals($position)) {
                unset($this->signs[$index]);
                $this->saveSigns();
                return;
            }
        }
    }

    public function updateSigns(): void {
        foreach ($this->signs as $sign) {
            $this->updateSign($sign["position"], $sign["arenaId"]);
        }
    }

    private function updateSign(Position $position, string $arenaId): void {
        $arena = $this->arenaManager->getArena($arenaId);
        if ($arena !== null) {
            $tile = Server::getInstance()->getWorldManager()->getDefaultWorld()->getBlock($position);
            if ($tile instanceof BaseSign) {
                $tile->setText(new SignText( [
                    "§aArena: §f" . $arena->getName(),
                    "§bPlayers: §f" . count($arena->getPlayers()) . "§7/§f" . count($arena->getSpawnPoints()),
                    "§eStatus: §f" . $arena->getStatus(),
                    ""
                ]));
            }
        }
    }

    public function getSignByPosition(Position $position): ?array {
        foreach ($this->signs as $sign) {
            if ($sign["position"]->equals($position)) {
                return $sign;
            }
        }
        return null;
    }

    public function saveSigns(): void {
        $data = [];
        foreach ($this->signs as $sign) {
            $data[] = [
                "position" => [
                    "x" => $sign["position"]->getX(),
                    "y" => $sign["position"]->getY(),
                    "z" => $sign["position"]->getZ(),
                    "level" => $sign["position"]->getWorld()->getFolderName()
                ],
                "arenaId" => $sign["arenaId"]
            ];
        }
        file_put_contents(Loader::getInstance()->getDataFolder() . "signs.json", json_encode($data));
    }

    public function loadSigns(): void {
        if (!file_exists(Loader::getInstance()->getDataFolder() . "signs.json")) {
            return;
        }

        $data = json_decode(file_get_contents(Loader::getInstance()->getDataFolder() . "signs.json"), true);
        foreach ($data as $signData) {
            Server::getInstance()->getWorldManager()->loadWorld($signData["position"]["level"]);
            $world = Loader::getInstance()->getServer()->getWorldManager()->getWorldByName($signData["position"]["level"]);
            if ($world !== null) {
                $position = new Position($signData["position"]["x"], $signData["position"]["y"], $signData["position"]["z"], $world);
                $this->signs[] = [
                    "position" => $position,
                    "arenaId" => $signData["arenaId"]
                ];
                $this->updateSign($position, $signData["arenaId"]);
            }
        }
    }
}