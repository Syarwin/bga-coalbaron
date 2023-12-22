<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Managers\Meeples;
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
            SPACE_MINING_10,
        );
        if($nbPlayers>=4){
            $spaces[] = SPACE_MINING_4;
        }
        if($nbPlayers>=3){
            $spaces[] = SPACE_MINING_8;
        }
        return $spaces;
    }
    
    /**
     * FOLLOW THE RULES of ACTION 2 
     */
    function placeWorkerInMining($player, $space){
        self::trace("placeWorkerInMining($space)...");
        switch($space)
        {
            case SPACE_MINING_4: 
                $nbMoves = 4; 
                break;
            case SPACE_MINING_6: 
                $nbMoves = 6; 
                break;
            case SPACE_MINING_8: 
                $nbMoves = 8; 
                break;
            case SPACE_MINING_10: 
                $nbMoves = 10; 
                break;
            default:
                throw new \BgaVisibleSystemException("Not supported mining worker space : $space");
        }
        Meeples::placeWorkersInSpace($player,$space);
        Globals::setMiningMoves($nbMoves);
        Notifications::startMining($player,$nbMoves);
        //Go to another state to manage moves :
        $this->gamestate->nextState( 'startMining' );
    }
}
