<?php

namespace com\Skywars;

use com\Skywars\entity\EntityManager;
use com\Skywars\sessions\SessionManager;
use com\Skywars\task\UpdateSign;
use pocketmine\block\BaseSign;
use pocketmine\block\BlockTypeIds;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\TickingAreasLoadStatusPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ItemUseOnBlockSound;

class EventListener implements Listener
{

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        SessionManager::createSession($player);
        $player->sendMessage("Welcome to the server! Your session has been created.");
    }


    public function onPlayerChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $session = SessionManager::getSession($player);

        if ($session !== null && $session->isInDeveloperMode()) {
            $args = explode(' ', $message);

            switch ($args[0]) {
                case 'setspawn':
                    if (isset($args[1]) && is_numeric($args[1])) {
                        $slot = (int) $args[1];
                        $arenaId = $session->getArenaId();

                        if ($arenaId !== null) {
                            $arena = Loader::getInstance()->getArenaManager()->getArena($arenaId);
                            Loader::getInstance()->getArenaManager()->setSpawnPoint($arena, $slot, $player->getPosition());
                            $player->sendMessage("Spawn point $slot set for arena $arenaId.");
                            $event->cancel();
                        } else {
                            $player->sendMessage("You are not in any arena setup mode.");
                        }
                    }
                    break;

                case 'setlobby':
                    $arenaId = $session->getArenaId();

                    if ($arenaId !== null) {
                        $arena = Loader::getInstance()->getArenaManager()->getArena($arenaId);
                        Loader::getInstance()->getArenaManager()->setLobbyPoint($arena, $player->getPosition());
                        $player->sendMessage("Lobby point set for arena $arenaId.");
                        $event->cancel();
                    } else {
                        $player->sendMessage("You are not in any arena setup mode.");
                    }
                    break;

                case 'setvoid':
                    $arenaId = $session->getArenaId();

                    if ($arenaId !== null) {
                        $arena = Loader::getInstance()->getArenaManager()->getArena($arenaId);
                        Loader::getInstance()->getArenaManager()->setVoidRegion($arena, $player->getPosition());
                        $player->sendMessage("Void region set for arena $arenaId.");
                        $event->cancel();
                    } else {
                        $player->sendMessage("You are not in any arena setup mode.");
                    }
                    break;

                case 'save':
                    $arenaId = $session->getArenaId();

                    if ($arenaId !== null) {
                        $session->setDeveloperMode(false);
                        $player->sendMessage("All settings have been saved and you have exited developer mode for arena $arenaId.");
                        $event->cancel();
                    } else {
                        $player->sendMessage("You are not in any arena setup mode.");
                    }
                    break;

                default:
                    $player->sendMessage("Unknown command in developer mode.");
                    break;
            }
        }
    }


    public function onBlockItemUseOn(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = Server::getInstance()->getWorldManager()->getDefaultWorld()->getBlock($block->getPosition());
        if ($tile instanceof BaseSign && Loader::getInstance()->isSignPlayer($player)) {
            $position = $block->getPosition();
            $arena = Loader::getInstance()->getArenaManager()->getAvailableArena();

            if ($arena !== null) {
                Loader::getInstance()->getSignManager()->addSign($block->getPosition(), $arena->getId());
                $player->sendMessage("Sign set for arena '{$arena->getName()}' at position {$position->getX()}, {$position->getY()}, {$position->getZ()}.");
                Loader::getInstance()->removeSignPlayer($player);
                $event->cancel();
            } else {
                $player->sendMessage("No available arenas found.");
            }
        } else {
            if ($block instanceof BaseSign) {
                $signData = Loader::getInstance()->getSignManager()->getSignByPosition($block->getPosition());
                if ($signData !== null) {
                    $arena = Loader::getInstance()->getArenaManager()->getArena($signData["arenaId"]);
                    if ($arena !== null) {
                        $arena->addPlayer($player);
                    } else {
                        $player->sendMessage(TextFormat::RED . "Arena not found.");
                    }
                }
                $event->cancel();
            }
        }
    }

    public function onInteractEntity(PlayerEntityInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $entity = $event->getEntity();
        EntityManager::handleEntityTouch($player, $entity);
    }


    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            $arena = Loader::getInstance()->getArenaManager()->getArenaByPlayer($entity);
            if ($arena !== null) {
                if ($entity->getHealth() - $event->getFinalDamage() <= 0) {
                    $event->cancel();
                    $damager = null;

                    if ($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if ($damager instanceof Player) {
                            $message = "{$entity->getName()} was slain by {$damager->getName()}.";
                        } else {
                            $message = "{$entity->getName()} was slain by an entity.";
                        }
                    } elseif ($event instanceof EntityDamageByChildEntityEvent) {
                        $damager = $event->getDamager();
                        $child = $event->getChild();
                        if ($damager instanceof Player) {
                            $message = "{$entity->getName()} was shot by {$damager->getName()} with a {$child->getName()}.";
                        } else {
                            $message = "{$entity->getName()} was shot by an entity with a {$child->getName()}.";
                        }
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                        $message = "{$entity->getName()} fell to their death.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_DROWNING) {
                        $message = "{$entity->getName()} drowned.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_FIRE || $event->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK) {
                        $message = "{$entity->getName()} burned to death.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_LAVA) {
                    $message = "{$entity->getName()} tried to swim in lava.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION) {
                    $message = "{$entity->getName()} suffocated.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_CONTACT) {
                    $message = "{$entity->getName()} was pricked to death.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION || $event->getCause() === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION) {
                        $message = "{$entity->getName()} blew up.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_MAGIC) {
                    $message = "{$entity->getName()} was killed by magic.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_PROJECTILE) {
                    $message = "{$entity->getName()} was shot.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_SUICIDE) {
                    $message = "{$entity->getName()} committed suicide.";
                    } elseif ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                    $message = "{$entity->getName()} fell out of the world.";
                    } else {
                        $message = "{$entity->getName()} died.";
                    }

                    $arena->broadcastMessage($message);
                    $arena->handlePlayerDeath($entity, $damager);
                }
            }
        }
    }
}