<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;

use COAL\Managers\Players;
use COAL\Managers\Tiles;

/*
functions about workers at the Mining spaces
*/
trait WorkerAtMiningTrait
{
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Mining"
     */
    function getPossibleSpacesInMining($pId, $nbPlayers) {
        $spaces = array(
            SPACE_MINING_6,
            SPACE_MINING_8,
            SPACE_MINING_10,
        );
        if($nbPlayers>=4){
            $spaces[] = SPACE_MINING_4;
        }
        return $spaces;
    }
}
