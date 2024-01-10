<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Helpers\Collection;
use COAL\Helpers\Utils;
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
        $nbMoves = Globals::getMiningMoves();
        $player = Players::getActive();
        $cageLevel = $player->getCageLevel();
        $cagePossiblesLevels = self::getPossiblesMovesForPitCage($cageLevel);
        $movableCoals = $this->getPossiblesMovesForCoals($player,$nbMoves);

        return array(
        'cageLevel' => $cageLevel,
        'movableCage' => $cagePossiblesLevels,
        //'coals' => Meeples::getCoalsUiData($player->getId()),
        // KEEP MOVABLE only
        'movableCoals' => $movableCoals,
        'moves' => $nbMoves,
        'totalMoves' => Globals::getMiningMovesTotal(),
        );
    }

    function actStopMining()
    {
        self::checkAction( 'actStopMining' ); 
        $moves = Globals::getMiningMoves();
        $movesTotal = Globals::getMiningMovesTotal();
        $player = Players::getActive();
        Notifications::stopMining($player, $moves, $movesTotal);

        $this->gamestate->nextState( 'end' );
    }

    function actMovePitCage($toLevel)
    {
        self::checkAction( 'actMovePitCage' ); 

        $moves = Globals::getMiningMoves();

        // ANTICHEATS :
        if($moves <= 0) 
            throw new \BgaVisibleSystemException("Not enough work steps to play");
        $player = Players::getActive();
        $cagePossiblesLevels = self::getPossiblesMovesForPitCage($player->getCageLevel());
        if(!in_array($toLevel,$cagePossiblesLevels))
            throw new \BgaVisibleSystemException("Incorrect destination for your pit cage : $toLevel");

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
        foreach($coalIdArray as $coalId){
            $coal = Meeples::get($coalId);
            $this->canMoveToPitCage($coal,$player,count($coalsInCage),true);
            $coal->moveToCage($player);
            Globals::incMiningMoves(-1);
            Notifications::moveCoalToCage($player,$coalId);
            Stats::inc( "miningMovesDone", $player );
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
        foreach($coalIdArray as $coalId){
            $coal = Meeples::get($coalId);
            $this->canMoveToStorage($coal,$player,true);
            $coal->moveToStorage($player);
            Globals::incMiningMoves(-1);
            Notifications::moveCoalToStorage($player,$coalId);
            Stats::inc( "miningMovesDone", $player );
        }
    }
    /**
     * ANTICHEAT CHECKS + mining action of moving cubes to an order card
     */
    function prepareMoveCoalsToCard($player,$coalIdArray,$cardId,$spotIndex){
        self::trace("prepareMoveCoalsToCard($cardId,$spotIndex)...");
        $card = Cards::get($cardId);
        $cardCoalsStatus = Cards::getCardCoalsStatus($cardId);
        foreach($coalIdArray as $coalId){//loop cubes (1 cube in majority, 2 cubes sometimes)
            $coal = Meeples::get($coalId);
            $this->canMoveToCard($coal,$player,$card,$cardCoalsStatus,$spotIndex,count($coalIdArray), true);

            $coal->moveToCard($cardId,$spotIndex);
            Globals::incMiningMoves(-1);
            Notifications::moveCoalToCard($player,$cardId,$spotIndex,$coalId);
            Stats::inc( "coalsPlacedOnCard", $player );
            Stats::inc( "miningMovesDone", $player );
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
        
        Stats::inc( "nbActions2", $player );
        Stats::inc( "miningMovesReceived", $player, $nbMoves );
        //Go to another state to manage moves :
        $this->gamestate->nextState( 'startMining' );
    }
    
    /**
     * @return array levels to move the cage from current specified level
     */
    static function getPossiblesMovesForPitCage($currentCageLevel){
        self::trace("getPossiblesMovesForPitCage($currentCageLevel)...");
        $cageLevelsPossibles = range(LEVEL_SURFACE,LEVEL_TUNNEL_MAX);
        Utils::filter($cageLevelsPossibles, fn ($m) => $m != $currentCageLevel);
        return $cageLevelsPossibles;
    }

    /**
     * Study each player coal
     * @param Player $player
     * @param int $nbMoves number of moves to play
     * @return Collection of array : return list of location to send a coal (empty by default)
     */
    function getPossiblesMovesForCoals($player,$nbMoves){
        self::trace("getPossiblesMovesForCoals($nbMoves)...");
        $pId = $player->getId();
        $coals = Meeples::getPlayerCoals($pId);
        $coalsInCage = Meeples::getPlayerCageCoals($pId);
        $nbCoalsInCage = count($coalsInCage);
        $pendingOrders = Cards::getPlayerPendingOrders($pId);
        foreach($pendingOrders as $cardId => $card) {
            $cardCoalsStatusArray[$cardId] = Cards::getCardCoalsStatus($cardId);
        }
        
        $possibleMoves = [];
        //Check moving coals by 1
        $possibleMoves["solo"] = $coals->map(function ($coal) use ($player, $nbCoalsInCage, $pendingOrders, $cardCoalsStatusArray) {
            $possibleLocations = [];
            //Send another array for possibles moves in combination with another cube
            if($this->canMoveToPitCage($coal,$player,$nbCoalsInCage)){
                $possibleLocations[] = SPACE_PIT_CAGE;
            }
            if($this->canMoveToStorage($coal,$player)){
                $possibleLocations[] = COAL_LOCATION_STORAGE;
            }
            foreach($pendingOrders as $cardId => $card){
                $cardCoalsStatus = $cardCoalsStatusArray[$cardId];
                $maxSpot = count($cardCoalsStatus);
                for($spotIndex = 0; $spotIndex < $maxSpot; $spotIndex++ ){
                    if($this->canMoveToCard($coal,$player,$card,$cardCoalsStatus,$spotIndex,1)){
                        $possibleLocations[] = COAL_LOCATION_CARD.$cardId.'_'.$spotIndex;
                    }
                    else if(!in_array("duo",$possibleLocations) && $this->canMoveToCard($coal,$player,$card,$cardCoalsStatus,$spotIndex,2)){
                        //Don't resend all duo array, only a reference to it
                        $possibleLocations[] = "duo";
                    }
                }
            }
            return $possibleLocations;
          });
        
        //Check moving coals by 2
        $possibleMoves["duo"] = [];
        if($nbMoves >= 2 ){
            //use fake coal to check empty spots in the same way :
            $coal = new CoalCube(["player_id" => $pId, "meeple_location" => COAL_LOCATION_STORAGE, ], []);
            foreach($pendingOrders as $cardId => $card){
                $cardCoalsStatus = $cardCoalsStatusArray[$cardId];
                $maxSpot = count($cardCoalsStatus);
                for($spotIndex = 0; $spotIndex < $maxSpot; $spotIndex++ ){
                    if($this->canMoveToCard($coal,$player,$card,$cardCoalsStatus,$spotIndex,2)){
                        $possibleMoves["duo"][] = COAL_LOCATION_CARD.$cardId.'_'.$spotIndex;
                    }
                }
            }
        }
        return $possibleMoves;
    }
    /**
     * @param CoalCube $coal
     * @param Player $player
     * @param int $nbCoalsInCage
     * @param bool $throwErrors (optional) true if we want to throw specific exception, or false if we want to return false
     * @return bool false if this coal doesn't respect conditions to move to the cage, true otherwise
     */
    static function canMoveToPitCage($coal,$player,$nbCoalsInCage,$throwErrors = false){
        if($nbCoalsInCage > SPACE_PIT_CAGE_MAX){
            if($throwErrors) throw new \BgaVisibleSystemException("The pit cage is full");
            return false;
        }
        if($coal->getPId() != $player->getId() ) {
            if($throwErrors) throw new \BgaVisibleSystemException("Coal cube doesn't belong to you");
            return false;
        }
        $location = $coal->getLocation(); 
        if(!( str_starts_with($location,COAL_LOCATION_TILE) || str_starts_with($location,SPACE_PIT_TILE))){
            if($throwErrors) throw new \BgaVisibleSystemException("Coal cube cannot be moved to the pit cage from $location");
            return false;
        }
        $row = null;
        $test = SPACE_PIT_TILE;
        if (preg_match("/^${test}_(?P<row>\d+)_(?P<col>[-]*\d+)$/", $location, $matches ) == 1) {
            $row = $matches['row'];
        }
        else if (preg_match("/^".COAL_LOCATION_TILE."(?P<tile>\d+)$/", $location, $matches ) == 1) {
            $tileId = $matches['tile'];
            $tile = Tiles::get($tileId);
            $row = $tile->getY();
        }
        if($row != $player->getCageLevel()){
            if($throwErrors) throw new \BgaVisibleSystemException("Pit cage is not at this level : $row");
            return false;
        }
        return true;
    }
    /**
     * @param CoalCube $coal
     * @param int $cageLevel
     * @param bool $throwErrors (optional) true if we want to throw specific exception, or false if we want to return false
     * @return bool false if this coal doesn't respect conditions to move to the storage, true otherwise
     */
    static function canMoveToStorage($coal,$player,$throwErrors = false){
        if($player->getCageLevel() > LEVEL_SURFACE){
            if($throwErrors) throw new \BgaVisibleSystemException("The pit cage is not at the surface level");
            return false;
        }
        if($coal->getPId() != $player->getId() ) {
            if($throwErrors) throw new \BgaVisibleSystemException("Coal cube doesn't belong to you");
            return false;
        }
        $location = $coal->getLocation(); 
        if($location != SPACE_PIT_CAGE){
            if($throwErrors) throw new \BgaVisibleSystemException("Coal cube is not in the pit cage");
            return false;
        }
        return true;
    }
    /**
     * CHECKS DATAS 
     * @param CoalCube $coal
     * @param Player $player
     * @param Card $card
     * @param array $cardCoalsStatus
     * @param int $spotIndex
     * @param int $nbMovingCubes 1 or 2
     * @param bool $throwErrors (optional) true if we want to throw specific exception, or false if we want to return false
     * @return bool true if this coal can be moved to that card on that spot index
     */
    static function canMoveToCard($coal,$player,$card,$cardCoalsStatus,$spotIndex, $nbMovingCubes,
        $throwErrors = false){
        
        if($card->getPId() != $player->getId() ) {
            if($throwErrors) throw new \BgaVisibleSystemException("Card doesn't belong to you");
            return false;
        }
        //CHECK CARD is in pending orders !
        if( $card->getLocation() != CARD_LOCATION_OUTSTANDING){
            if($throwErrors) throw new \BgaVisibleSystemException("Card is not to be loaded with cubes");
            return false;
        }
        //CHECK CARD spotIndex < SIZE because index starts at 0
        if($spotIndex >= count($cardCoalsStatus)){
            if($throwErrors) throw new \BgaVisibleSystemException("Incorrect spot index $spotIndex for a cube on this card");
            return false;
        }
        //CHECK CARD EMPTY SPACES
        $currentStatus = $cardCoalsStatus[$spotIndex];
        $neededColor = array_key_first($currentStatus);
        if($currentStatus[$neededColor] != COAL_EMPTY_SPOT){
            if($throwErrors) throw new \BgaVisibleSystemException("Spot $spotIndex is not empty on this card");
            return false;
        }

        if($coal->getPId() != $player->getId() ) {
            if($throwErrors) throw new \BgaVisibleSystemException("Coal cube doesn't belong to you");
            return false;
        }
        $location = $coal->getLocation(); 
        if(!( $location == COAL_LOCATION_STORAGE || $location == SPACE_PIT_CAGE && $player->getCageLevel() == LEVEL_SURFACE)){
            if($throwErrors) throw new \BgaVisibleSystemException("Coal cube cannot be moved to an order card from $location");
            return false;
        }
        //CHECK 1 moving cube MUST BE the same color on the card spot, 2 cubes are free to use any colors (even the right one)
        $coalColor = $coal->getType();
        if($coalColor != $neededColor && $nbMovingCubes == 1){
            if($throwErrors) throw new \BgaVisibleSystemException("Coal cube $coalColor is not the right color : $neededColor");
            return false;
        }
        return true;
    }
}
