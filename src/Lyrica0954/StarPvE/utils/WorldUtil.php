<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\Server;
use pocketmine\world\World;

class WorldUtil {

    public static function cloneWorld(string $name, string $dst): ?string{
        $server = Server::getInstance();
        $worldPath = $server->getDataPath() . "worlds/";
        $filePath = $worldPath . $name;
        $targetPath = $worldPath . $dst . "/";
        if (file_exists($filePath)){
            if (!file_exists($targetPath)){
                mkdir($targetPath);
            }
            self::recursive_copy($filePath, $targetPath);
            return $targetPath;
        }
        return null;
    }

    public static function deleteWorld(World $world){
        $server = Server::getInstance();
        $path = self::getWorldPathFromName($world->getFolderName());
        self::remove_dir($path);
    }

    public static function getWorldPathFromName(String $name){
        $server = Server::getInstance();
        $worldPath = $server->getDataPath() . "worlds/";
        $filePath = $worldPath . $name;
        return $filePath;
    }

    public static function getWorlds(){
        $server = Server::getInstance();
        $worldPath = $server->getDataPath() . "worlds/";
        $list = glob($worldPath. "*", GLOB_ONLYDIR);
        return $list;
    }

    public static function getTrueWorlds(){
        $list = self::getWorlds();
        $server = Server::getInstance();
        foreach($list as $path){
            $name = basename($path);
            $wm = $server->getWorldManager();
            $wm->loadWorld($name);
            $world = $server->getWorldManager()->getWorldByName($name);
            if ($world !== null){
                yield $world;
            }
        }
    }

    protected static function recursive_copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::recursive_copy($src .'/'. $file, $dst .'/'. $file);
                }
                else {
                    copy($src .'/'. $file,$dst .'/'. $file);
                }
            }
        }
        closedir($dir);
    }

    protected static function remove_dir($path){
        $list = scandir($path);
        $length = count($list);
        for ($i=0; $i<$length; $i++){
            if ($list[$i] != '.' && $list[$i] != '..'){
                if (is_dir($path.'/'.$list[$i])){
                    self::remove_dir($path.'/'.$list[$i]);
                } else {
                    unlink($path.'/'.$list[$i]);
                }
            }
        }
        rmdir($path);
    }
}