<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Managers\Players;

/*
functions about workers at the bank / money spaces
*/
trait WorkerAtBankTrait
{
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "bank"
     */
    function getPossibleSpacesInBank($pId, $nbPlayers) {
        $spaces = array(
            SPACE_BANK_1,
            SPACE_BANK_4,
            SPACE_BANK_6,
        );
        if($nbPlayers>=3){
            $spaces[] = SPACE_BANK_5;
        }
        if($nbPlayers>=4){
            $spaces[] = SPACE_BANK_3;
        }
        //TODO JSA FILTER on available meeples 
        return $spaces;
    }
}
