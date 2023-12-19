<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;

use COAL\Managers\Players;

/*
functions about workers at the Delivery spaces
*/
trait WorkerAtDeliveryTrait
{
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Delivery"
     */
    function getPossibleSpacesInDelivery($pId) {
        $spaces = array(
            SPACE_DELIVERY_BARROW,
            SPACE_DELIVERY_CARRIAGE,
            SPACE_DELIVERY_MOTORCAR,
            SPACE_DELIVERY_ENGINE,
        );

        //TODO JSA FILTER on player filled Orders (type)
        
        return $spaces;
    }
}