<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data;

use pocketmine\utils\Config;

abstract class DataCenter {

    protected array $data;
    protected string $folder;

    public function __construct(string $folder){
        $this->data = []; #V: Config
        $this->folder = $folder;
        $this->load($folder);
    }

    public function save(){
        foreach($this->data as $config){
            if ($config instanceof Config){
                $config->save();
            }
        }
    }

    public function reload(){
        foreach($this->data as $config){
            if ($config instanceof Config){
                $config->reload();
            }
        }
    }

    protected function load(string $folder){
        foreach(glob($folder . "/*.yml") as $file){
            $this->data[] = new Config($file);
        }
    }
}