<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Managers\Meeples;
use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Models\CoalCube;
use COAL\Models\Meeple;

/*
functions about workers at the Mining spaces
*/
trait WorkerAtMiningTrait
{
    
    function argMiningSteps(){
        $player = Players::getActive();

        return array(
        'cageLevel' => $player->getCageLevel(),
        'coals' => Meeples::getCoalsUiData($player->getId()),
        'moves' => Globals::getMiningMoves(),
        );
    }
    
    function actMovePitCage($toLevel)
    {
        self::checkAction( 'actMovePitCage' ); 

        $moves = Globals::getMiningMoves();

        // ANTICHEATS :
        if($moves <= 0) 
            throw new \BgaVisibleSystemException("Not enough work steps to play");
        if($toLevel <LEVEL_SURFACE && $toLevel > LEVEL_TUNNEL_MAX)
            throw new \BgaVisibleSystemException("Incorrect destination for your pit cage : $toLevel");

        $player = Players::getActive();
        $player->movePitCageTo($toLevel);
        
        $moves = Globals::getMiningMoves();
        if( $moves == 0){
        //END MINING STEPS
            $this->gamestate->nextState( 'end' );
            return;
        }
        //ELSE continue mining and resend args datas
        $this->gamestate->nextState( 'continue' );
    }
    function actMoveCoals($coalIdArray,$spaceId)
    {
        self::checkAction( 'actMoveCoals' ); 

        $moves = Globals::getMiningMoves();

        // ANTICHEATS :
        if($moves <= 0) 
            throw new \BgaVisibleSystemException("Not enough work steps to play");
        $player = Players::getActive();
        //TODO JSA ANTICHEAT : 1 coal color -> to order OR 2 Different to order
        //TODO JSA CLEAN with refactor...
        switch($spaceId){
            case SPACE_PIT_CAGE:
                $coalsInCage = Meeples::getPlayerCageCoals($player->getId());
                if(count($coalIdArray) > 1)
                    throw new \BgaVisibleSystemException("Only 1 coal cube at a time can be moved to the pit cage");
                if(count($coalsInCage) > SPACE_PIT_CAGE_MAX)
                    throw new \BgaVisibleSystemException("The pit cage is full");
                foreach($coalIdArray as $coalId){
                    $coal = Meeples::get($coalId);
                    if($coal->getPId() != $player->getId() ) {
                        throw new \BgaVisibleSystemException("Coal cube doesn't belong to you");
                    }
                    $location = $coal->getLocation(); 
                    if(!( str_starts_with($location,COAL_LOCATION_TILE) || str_starts_with($location,SPACE_PIT_TILE))){
                        throw new \BgaVisibleSystemException("Coal cube cannot be moved to the pit cage");
                    }
                  
                    if (preg_match("/^".SPACE_PIT_TILE."(?P<row>\d+)_(?P<col>(-)*\d+)$/", $location, $matches ) == 1) {
                        $row = $matches['row'];
                        if($row != $player->getCageLevel()){
                          throw new \BgaVisibleSystemException("Pit cage is not at this level : $row");
                        }
                    }
                    else if (preg_match("/^".COAL_LOCATION_TILE."(?P<tile>\d+)$/", $location, $matches ) == 1) {
                        $tileId = $matches['tile'];
                        $tile = Tiles::get($tileId);
                        if($tile->getY() != $player->getCageLevel()){
                          throw new \BgaVisibleSystemException("Pit cage is not at this level : ".$tile->getY());
                        }
                    }
                    $coal->moveToCage($player);
                    Notifications::moveCoalToCage($player,$coalId);
                }
                break;
            default:
                throw new \BgaVisibleSystemException("Not supported destination to move your coals : $spaceId");
        }
        
        $moves = Globals::getMiningMoves();
        if( $moves == 0){
        //END MINING STEPS
            $this->gamestate->nextState( 'end' );
            return;
        }
        //ELSE continue mining and resend args datas
        $this->gamestate->nextState( 'continue' );
    }
    /**
     * List all Worker Spaces to play on specified action "Mining"
     */
    function getAllSpacesInMining($nbPlayers) {
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
     * List all possible Worker Spaces to play by player $pId and specified action "Mining"
     */
    function getPossibleSpacesInMining($pId, $nbPlayers) {
        $spaces = $this->getAllSpacesInMining($nbPlayers);
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
