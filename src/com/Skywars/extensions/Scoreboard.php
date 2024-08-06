<?php

namespace com\Skywars\extensions;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;

class Scoreboard {
    private array $lines = [];
    private string $objectiveName;
    private string $displayName;

    public function __construct(string $objectiveName, string $displayName) {
        $this->objectiveName = $objectiveName;
        $this->displayName = $displayName;
    }

    public function addLine(int $score, string $message): void {
        $this->lines[$score] = $message;
    }

    public function removeLine(int $score): void {
        unset($this->lines[$score]);
    }

    public function updateLine(int $score, string $message): void {
        $this->lines[$score] = $message;
    }

    public function send(Player $player): void {
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = $this->objectiveName;
        $pk->displayName = $this->displayName;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->getNetworkSession()->sendDataPacket($pk);

        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_CHANGE;
        foreach ($this->lines as $score => $message) {
            $entry = new ScorePacketEntry();
            $entry->objectiveName = $this->objectiveName;
            $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
            $entry->customName = $message;
            $entry->score = $score;
            $entry->scoreboardId = $score;
            $pk->entries[] = $entry;
        }
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function remove(Player $player): void {
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $this->objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);
    }
}