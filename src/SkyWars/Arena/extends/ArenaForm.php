<?php

namespace SkyWars\Arena\extends;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SkyWars\Loader;
use SkyWars\Sessions\SessionManager;

class ArenaForm
{
    public static int $default = 12;

    public static function developed(Player $player): SimpleForm
    {
        $form = new SimpleForm(function (Player $player, $data = null){
            if($data === null){return;}
            switch ($data){
                case 0:
                    self::spawnsForm($player);
                    break;
            }
        });
        $form->setTitle('setup mode');
        $form->addButton('Spawn register!');
        $form->addButton('Save to date');
        $form->sendToPlayer($player);
        return $form;
    }
    public static function spawnsForm(Player $player): CustomForm
    {
        $form = new CustomForm(function (Player $player, $data = null){
            if($data === null){return;}
            $maxPlayers = $data[1];
            self::$default = $maxPlayers;
            $arenaName = SessionManager::getSession($player)->getDevelopedArena();
            if($arenaName !== null){
                $arena = Loader::getLoader()->getArenaManager()->getArena($arenaName);
                if($arena !== null){
                    $arena->setMaxplayer($maxPlayers);
                    Loader::getLoader()->getArenaManager()->updateArena($arena);
                    $player->sendMessage(TextFormat::GREEN.'Max players for arena '. $arenaName. ' set to '. $maxPlayers);
                    $stick = VanillaItems::STICK();
                    $stick->setCustomName(TextFormat::GOLD.'Spawn Point Setter');
                    $stick->getNamedTag()->setByte('SpawnSetter', 1);
                    $player->getInventory()->addItem($stick);
                }
            }
        });
        $form->setTitle('arena development');
        $form->addLabel('Configure your arena sertting fellow');
        $form->addInput('Maxium players', 'spawns: '. self::$default);
        $form->sendToPlayer($player);
        return $form;
    }
}