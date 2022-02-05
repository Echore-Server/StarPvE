<?php

declare(strict_types=1);


namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\StarPvE\game\Game;

class WaveData {

    protected string $titleFormat;

    protected ?CustomWaveStart $customWaveStart;

    public WaveMonsters $lane1;
    public WaveMonsters $lane2;
    public WaveMonsters $lane3;
    public WaveMonsters $lane4;

    public function __construct(string $titleFormat, ?CustomWaveStart $customWaveStart, WaveMonsters $lane1, WaveMonsters $lane2, WaveMonsters $lane3, WaveMonsters $lane4){
        $this->titleFormat = $titleFormat;
        $this->customWaveStart = $customWaveStart;
        
        $this->lane1 = $lane1;
        $this->lane2 = $lane2;
        $this->lane3 = $lane3;
        $this->lane4 = $lane4;
    }

    public function parseTitleFormat(int $wave){
        return sprintf($this->titleFormat, $wave);
    }

    public function getCustomWaveStart(): ?CustomWaveStart{
        return $this->customWaveStart;
    }
}