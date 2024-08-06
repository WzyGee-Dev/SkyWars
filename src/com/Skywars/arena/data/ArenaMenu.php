<?php

namespace com\Skywars\arena\data;

use com\Skywars\arena\Arena;
use com\Skywars\Loader;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ArenaMenu
{


    public static function openMenu(Player $player): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName('Arena Menu');
        foreach (Loader::getInstance()->getArenaManager()->getArenas() as $arena) {
            if($arena instanceof Arena){
                $status = $arena->getStatus();
                $block = self::getBlockByStatus($status);
                $block->setCustomName("§a" . $arena->getName());
                $block->setLore([
                    "§bPlayers: §f" . count($arena->getPlayers()) . "§7/§f" . count($arena->getSpawnPoints()),
                    "§eStatus: §f" . $arena->getStatus()
                ]);
                $block->getNamedTag()->setString('arena_id', $arena->getId());
                $menu->getInventory()->addItem($block);
            }
        }
        $menu->setListener(function (InvMenuTransaction $transaction) {
            $player = $transaction->getPlayer();
            $arenaId = $transaction->getItemClicked()->getNamedTag()->getString('arena_id', '');
            if (!empty($arenaId)) {
                $arena =Loader::getInstance()->getArenaManager()->getArena($arenaId);
                if ($arena !== null) {
                    $arena->addPlayer($player);
                } else {
                    $player->sendMessage("Arena not found.");
                }
            }
            $transaction->getPlayer()->removeCurrentWindow();
            return $transaction->discard();
        });
        $menu->send($player);
    }


    private static function getBlockByStatus(string $status): Item {
        return match ($status) {
            ArenaStatus::WAITING => VanillaBlocks::WOOL()->setColor(DyeColor::GREEN)->asItem(),
            ArenaStatus::STARTING => VanillaBlocks::WOOL()->setColor(DyeColor::ORANGE)->asItem(),
            ArenaStatus::IN_PROGRESS => VanillaBlocks::WOOL()->setColor(DyeColor::RED)->asItem(),
            ArenaStatus::RELOADING => VanillaBlocks::WOOL()->setColor(DyeColor::PURPLE)->asItem(),
            default => VanillaBlocks::WOOL()->setColor(DyeColor::WHITE)->asItem()
        };
    }
}