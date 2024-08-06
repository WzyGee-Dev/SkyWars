<?php

namespace com\Skywars\sessions;

use pocketmine\player\Player;

class Session
{
    private Player $player;
    private ?string $arenaId;
    private bool $isInDeveloperMode;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->arenaId = null;
        $this->isInDeveloperMode = false;
    }

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getArenaId(): ?string {
        return $this->arenaId;
    }

    public function setArenaId(string $arenaId): void {
        $this->arenaId = $arenaId;
    }

    public function isInDeveloperMode(): bool {
        return $this->isInDeveloperMode;
    }

    public function setDeveloperMode(bool $isInDeveloperMode): void {
        $this->isInDeveloperMode = $isInDeveloperMode;
    }
}