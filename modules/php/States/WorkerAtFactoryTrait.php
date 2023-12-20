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
    function getPossibleSpacesInFactory($pId) {
        $spaces = Tiles::getPossibleSpacesInFactory();
        //TODO JSA FILTER on available money VS cost 
        return $spaces;
    }
}
