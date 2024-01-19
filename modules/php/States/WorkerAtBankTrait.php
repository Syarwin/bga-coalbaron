<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Managers\Meeples;
use COAL\Managers\Players;

/*
functions about workers at the bank / money spaces
*/
trait WorkerAtBankTrait
{
    /**
     * List all Worker Spaces to play on specified action "bank"
     */
    function getAllSpacesInBank($nbPlayers) {
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
        return $spaces;
    }
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "bank"
     */
    function getPossibleSpacesInBank($pId, $nbPlayers) {
        self::trace("getPossibleSpacesInBank($pId, $nbPlayers)");
        return $this->getAllSpacesInBank($nbPlayers);
    }

    function placeWorkerInBank($player, $space){
        self::trace("placeWorkerInBank($space)...");
        $fixedNbNeededWorkers = null;
        switch($space)
        {
            case SPACE_BANK_3: 
                $money = 3; 
                break;
            case SPACE_BANK_4: 
                $money = 4; 
                break;
            case SPACE_BANK_5: 
                $money = 5; 
                break;
            case SPACE_BANK_6: 
                $money = 6; 
                break;
            case SPACE_BANK_1: 
                $money = 1; 
                $fixedNbNeededWorkers = 1;
                break;
            default:
                throw new \BgaVisibleSystemException("Not supported bank worker space : $space");
        }
        Meeples::placeWorkersInSpace($player,$space,$fixedNbNeededWorkers);
        Players::giveMoney($player,$money);
        Stats::inc( "nbActions4", $player );
    }
}
