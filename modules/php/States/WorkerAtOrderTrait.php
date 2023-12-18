<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;

use COAL\Managers\Players;

/*
functions about workers at the Order Cards spaces
*/
trait WorkerAtOrderTrait
{
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Order"
     */
    function getPossibleSpacesInOrder($pId, $nbPlayers) {
        $spaces = array(
            SPACE_ORDER_2,
            SPACE_ORDER_3,
            SPACE_ORDER_4,
            SPACE_ORDER_DRAW,
        );
        if($nbPlayers>=4){
            $spaces[] = SPACE_ORDER_1;
        }
        //TODO JSA FILTER on available meeples 
        return $spaces;
    }
}
