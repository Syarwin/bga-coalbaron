<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;

use COAL\Managers\Players;
use COAL\Managers\Tiles;

/*
functions about workers at the Lorry Factory spaces
*/
trait WorkerAtFactoryTrait
{
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Factory"
     */
    function getPossibleSpacesInFactory($pId, $nbPlayers) {
        $spaces = array(
            SPACE_FACTORY_1,
            SPACE_FACTORY_2,
            SPACE_FACTORY_3,
            SPACE_FACTORY_5,
            SPACE_FACTORY_6,
            SPACE_FACTORY_7,
            SPACE_FACTORY_DRAW,
        );
        
        if($nbPlayers>=4){
            $spaces[] = SPACE_FACTORY_4;
            $spaces[] = SPACE_FACTORY_8;
        }

        //TODO JSA FILTER on available meeples 
        //TODO JSA FILTER on available money VS cost 
        return $spaces;
    }
}
