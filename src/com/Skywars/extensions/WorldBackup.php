<?php

namespace com\Skywars\extensions;

use Exception;
use FilesystemIterator;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\io\data\BaseNbtWorldData;
use pocketmine\world\format\io\exception\CorruptedWorldException;
use pocketmine\world\World;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class WorldBackup
{

    public static function createBackup(string $newName, string $worldName): void
    {

        $worldManager = Server::getInstance()->getWorldManager();
        if ($worldManager->isWorldGenerated($newName)) {
            return;
        }
        if (!$worldManager->isWorldGenerated($worldName)) {
            throw new CorruptedWorldException("The world i try to copy is not generated");
        }
        $destination = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $newName;
        $source = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $worldName;
        self::copyDirectory($destination, $source);
        if (!Server::getInstance()->getWorldManager()->isWorldLoaded($newName)) {
            Server::getInstance()->getWorldManager()->loadWorld($newName);
        }
        $world = Server::getInstance()->getWorldManager()->getWorldByName($newName);
        if (empty($world)) {

            return;
        }
        $worldData = $world->getProvider()->getWorldData();
        if (!$worldData instanceof BaseNbtWorldData) {
            //  $this->setResult(false);
            return;
        }
        $worldData->getCompoundTag()->setString("LevelName", $newName);
        Server::getInstance()->getWorldManager()->unloadWorld($world);
        Server::getInstance()->getWorldManager()->loadWorld($newName);
    }

    public static function copyDirectory($dest, $source): void
    {
        if (is_dir($source)) {
            @mkdir($dest);
            $d = dir($source);
            while(FALSE !== ($entry = $d->read())) {
                if ($entry === "." or $entry === "..") {
                    continue;
                }
                $newEntry = $source . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($newEntry)) {
                    self::copyDirectory($dest . DIRECTORY_SEPARATOR . $entry, $newEntry);
                    continue;
                }
                @copy($newEntry, $dest . DIRECTORY_SEPARATOR . $entry);
            }
            $d->close();
        } else {
            @copy($source, $dest);
        }
    }

    public static function removeWorld(string $name): int {
        if(Server::getInstance()->getWorldManager()->isWorldLoaded($name)) {
            $world = self::getWorldByNameNonNull($name);
            if(count($world->getPlayers()) > 0) {
                foreach($world->getPlayers() as $player) {
                    $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                }
            }
        }

        $removedFiles = 1;

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($worldPath = Server::getInstance()->getDataPath() . "/worlds/$name", FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /** @var SplFileInfo $fileInfo */
        foreach($files as $fileInfo) {
            if($filePath = $fileInfo->getRealPath()) {
                if($fileInfo->isFile()) {
                    unlink($filePath);
                } else {
                    rmdir($filePath);
                }

                ++$removedFiles;
            }
        }

        rmdir($worldPath);
        return $removedFiles;
    }


    public static function getWorldByNameNonNull(string $name): World {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($name);
        if($world === null) {
            throw new AssumptionFailedError("Required world \"$name\" is null");
        }

        return $world;
    }
}