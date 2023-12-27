<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Managers\Cards;
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
        'totalMoves' => Globals::getMiningMovesTotal(),
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
        if($moves <= 0 || count($coalIdArray) > $moves) 
            throw new \BgaVisibleSystemException("Not enough work steps to play");
        
        $player = Players::getActive();
        switch($spaceId){
            case SPACE_PIT_CAGE:
                $this->prepareMoveCoalsToCage($player,$coalIdArray);
                break;
            case COAL_LOCATION_STORAGE:
                $this->prepareMoveCoalsToStorage($player,$coalIdArray);
                break;
            default:
                if (preg_match("/^".COAL_LOCATION_CARD."(?P<cardId>\d+)_(?P<spotIndex>(-)*\d+)$/", $spaceId, $matches ) == 1) {
                    $this->prepareMoveCoalsToCard($player,$coalIdArray,$matches['cardId'],$matches['spotIndex']);
                    break;
                }
                else throw new \BgaVisibleSystemException("Not supported destination to move your coals : $spaceId");
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
     * ANTICHEAT CHECKS + mining action of moving 1 cube to pit cage -elevator
     */
    function prepareMoveCoalsToCage($player,$coalIdArray){
        self::trace("prepareMoveCoalsToCage()...");
        $coalsInCage = Meeples::getPlayerCageCoals($player->getId());
        if(count($coalIdArray) > 1){
            throw new \BgaVisibleSystemException("Only 1 coal cube at a time can be moved to the pit cage");
        }
        if(count($coalsInCage) > SPACE_PIT_CAGE_MAX){
            throw new \BgaVisibleSystemException("The pit cage is full");
        }
        foreach($coalIdArray as $coalId){
            $coal = Meeples::get($coalId);
            if($coal->getPId() != $player->getId() ) {
                throw new \BgaVisibleSystemException("Coal cube doesn't belong to you");
            }
            $location = $coal->getLocation(); 
            if(!( str_starts_with($location,COAL_LOCATION_TILE) || str_starts_with($location,SPACE_PIT_TILE))){
                throw new \BgaVisibleSystemException("Coal cube cannot be moved to the pit cage from $location");
            }
            $row = null;
            $test = SPACE_PIT_TILE;
            if (preg_match("/^${test}_(?P<row>\d+)_(?P<col>[-]*\d+)$/", $location, $matches ) == 1) {
                $row = $matches['row'];
                self::trace("prepareMoveCoalsToCage()... MOVING from PIT row $row");
            }
            else if (preg_match("/^".COAL_LOCATION_TILE."(?P<tile>\d+)$/", $location, $matches ) == 1) {
                $tileId = $matches['tile'];
                $tile = Tiles::get($tileId);
                $row = $tile->getY();
                self::trace("prepareMoveCoalsToCage()... MOVING from TILE row $row");
            }
            if($row != $player->getCageLevel()){
                throw new \BgaVisibleSystemException("Pit cage is not at this level : $row");
            }
            $coal->moveToCage($player);
            Globals::incMiningMoves(-1);
            Notifications::moveCoalToCage($player,$coalId);
        }
    }
    /**
     * ANTICHEAT CHECKS + mining action of moving 1 cube to private storage
     */
    function prepareMoveCoalsToStorage($player,$coalIdArray){
        self::trace("prepareMoveCoalsToStorage()...");
        if(count($coalIdArray) > 1){
            throw new \BgaVisibleSystemException("Only 1 coal cube at a time can be moved to the storage");
        }
        if($player->getCageLevel() > LEVEL_SURFACE){
            throw new \BgaVisibleSystemException("The pit cage is not at the surface level");
        }
        foreach($coalIdArray as $coalId){
            $coal = Meeples::get($coalId);
            if($coal->getPId() != $player->getId() ) {
                throw new \BgaVisibleSystemException("Coal cube doesn't belong to you");
            }
            $location = $coal->getLocation(); 
            if($location != SPACE_PIT_CAGE){
                throw new \BgaVisibleSystemException("Coal cube is not in the pit cage");
            }
            $coal->moveToStorage($player);
            Globals::incMiningMoves(-1);
            Notifications::moveCoalToStorage($player,$coalId);
        }
    }
    /**
     * ANTICHEAT CHECKS + mining action of moving cubes to an order card
     */
    function prepareMoveCoalsToCard($player,$coalIdArray,$cardId,$spotIndex){
        self::trace("prepareMoveCoalsToCard($cardId,$spotIndex)...");
        $card = Cards::get($cardId);
        if($card->getPId() != $player->getId() ) {
            throw new \BgaVisibleSystemException("Card doesn't belong to you");
        }
        $cardCoalsStatus = Cards::getCardCoalsStatus($cardId);
        //CHECK CARD spotIndex < SIZE because index starts at 0
        if($spotIndex >= count($cardCoalsStatus)){
            throw new \BgaVisibleSystemException("Incorrect spot index $spotIndex for a cube on this card");
        }
        //CHECK CARD EMPTY SPACES
        $currentStatus = $cardCoalsStatus[$spotIndex];
        $neededColor = array_key_first($currentStatus);
        if($currentStatus[$neededColor] != COAL_EMPTY_SPOT){
            throw new \BgaVisibleSystemException("Spot $spotIndex is not empty on this card");
        }
        foreach($coalIdArray as $coalId){//loop cubes (1 cube in majority, 2 cubes sometimes)
            $coal = Meeples::get($coalId);
            if($coal->getPId() != $player->getId() ) {
                throw new \BgaVisibleSystemException("Coal cube doesn't belong to you");
            }
            //CHECK COAL ORIGIN
            $location = $coal->getLocation(); 
            if(!( $location == COAL_LOCATION_STORAGE || $location == SPACE_PIT_CAGE)){
                throw new \BgaVisibleSystemException("Coal cube cannot be moved to an order card from $location");
            }
            //CHECK 1 moving cube MUST BE the same color on the card spot, 2 cubes are free to use any colors (even the right one)
            $coalColor = $coal->getType();
            if($coalColor != $neededColor && count($coalIdArray) == 1){
                throw new \BgaVisibleSystemException("Coal cube $coalColor is not the right color : $neededColor");
            }
            $coal->moveToCard($cardId,$spotIndex);
            Globals::incMiningMoves(-1);
            Notifications::moveCoalToCard($player,$cardId,$spotIndex,$coalId);
        }
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
        Globals::setMiningMovesTotal($nbMoves);
        Notifications::startMining($player,$nbMoves);
        //Go to another state to manage moves :
        $this->gamestate->nextState( 'startMining' );
    }
}
