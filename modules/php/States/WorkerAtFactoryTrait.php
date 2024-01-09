<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Exceptions\MissingCoalException;
use COAL\Managers\Meeples;
use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Models\Player;
use COAL\Models\TileCard;

/*
functions about workers at the Minecarts Factory spaces
*/
trait WorkerAtFactoryTrait
{
    function argChooseTile(){
        $player = Players::getActive();
        $player_id = $player->getId();
        $playerMoney = $player->getMoney();
        $privateDatas = array ();

        $tiles = Tiles::getInLocation(TILE_LOCATION_DRAW)->map(function ($tile) use ($playerMoney) {
            return $tile->getUiData($playerMoney);
          }) ->toAssoc();

        $privateDatas[$player_id] = array(
            'tiles' => $tiles,
        );

        return array(
            '_private' => $privateDatas,
        );
    }
    
    /**
     * Action of choosing 0-1 card and returning the others in wanted order 
     * @param int $tileId Id of the tile to keep (optional)
     * @param TOP|BOTTOM $returnDest (comes from action enum)
     * @param array $otherCardsOrder Order of tiles ids to put on $returnDest 
     */
    function actChooseTile($tileId, $returnDest, $othersTilesOrder){
        self::checkAction( 'actChooseTile' ); 
        self::trace("actChooseCard($tileId, $returnDest)...");

        $player = Players::getActive();
        $cardsNb = 0;

        //ANTICHEATS :
        if(isset($tileId)){
            //IF player keeps a card
            $tile = Tiles::get($tileId);
            if($tile->getLocation() != TILE_LOCATION_DRAW){
              throw new \BgaVisibleSystemException("Tile $tileId is not selectable");
            }
            //$cardsNb++;
            $this->giveTileToPlayer($player,$tile);
            Stats::inc( "tilesDrawn", $player );
        }
        $cardsNb += count($othersTilesOrder);
        
        $expectedCount = Tiles::countInLocation(TILE_LOCATION_DRAW);
        if($cardsNb != $expectedCount)
            throw new \BgaVisibleSystemException("Wrong number of cards : $cardsNb != $expectedCount");

        if($returnDest == "TOP") {
            Tiles::moveAllToTop($othersTilesOrder,TILE_LOCATION_DRAW,TILE_LOCATION_DECK);
            Notifications::returnTilesToTop($player,count($othersTilesOrder));
        } else {
            Tiles::moveAllToBottom($othersTilesOrder,TILE_LOCATION_DRAW,TILE_LOCATION_DECK);
            Notifications::returnTilesToBottom($player,count($othersTilesOrder));
        }
    
        if( ST_CHOOSE_TILE == $this->gamestate->state_id()){
            //GO TO NEXT STATE ONLY IF not already changed by a previous method
            $this->gamestate->nextState('next');
        }
    }
    /**
     * List all Worker Spaces to play in "Factory"
     */
    function getAllSpacesInFactory() {
        $spaces = Tiles::getUnlockedSpacesInFactory();
        return $spaces;
    }
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Factory"
     */
    function getPossibleSpacesInFactory($pId) {
        $spaces = $this->getAllSpacesInFactory();
        //TODO JSA PERFS : add $money in param to avoid another query
        $money = Players::get($pId)->getMoney();

        // FILTER on available money VS cost 
        // & FILTER EMPTY TILE (because deck may be empty )
        $filter = function ($space) use ($money) {
            //FACTORY DRAW ALWAYS POSSIBLE with 0 money 
            if($space == SPACE_FACTORY_DRAW) return true;//TODO JSA CHECK DECKSIZE
            $tile = Tiles::getTileInFactory($space);
            if($tile == null || $tile->getCost()> $money){
                return false;
            }
            return true;
        };

        $spaces = array_values(array_filter($spaces,$filter));
        return $spaces;
    }
    
    /**
     * FOLLOW THE RULES of ACTION 1 
     */
    function placeWorkerInFactory($player, $space){
        self::trace("placeWorkerInFactory($space)...");
        Meeples::placeWorkersInSpace($player,$space);
        $tile = Tiles::getTileInFactory($space);
        $this->giveTileToPlayer($player,$tile);

        //TODO JSA refillFactorySpace only when player confirmed the turn 
        $newTile = Tiles::refillFactorySpace($space);
        Notifications::refillFactorySpace($newTile);
        Stats::inc( "nbActions1", $player );
    }
    
     /**
     * FOLLOW THE RULES of ACTION 1 - SPECIAL CASE : drawing 5 tiles
     */
    function placeWorkerInDrawFactory($player){
        self::trace("placeWorkerInDrawFactory()...");
        Meeples::placeWorkersInSpace($player,SPACE_FACTORY_DRAW);

        $tiles = Tiles::pickForLocation(TILES_DRAW_NUMBER,TILE_LOCATION_DECK,TILE_LOCATION_DRAW);
        
        Stats::inc( "nbActions1", $player );

        //Go to another state to manage selection of tiles :
        $this->gamestate->nextState( 'chooseTile' );
    }
    
    /**
     * Move a tile to a given player
     */
    public function giveTileToPlayer($player,$tile)
    {
        Players::spendMoney($player,$tile->getCost());
        $column = Tiles::getPlayerNextColumnForTile($player->getId(),$tile);
        $tile->moveToPlayerBoard($player,$column);
        Stats::inc( "tilesReceived", $player );
        if($tile->isLight()){
            Stats::inc( "tilesLight", $player );
        }
        else {
            Stats::inc( "tilesDark", $player );
        }

        try {
            Meeples::placeCoalsOnTile($player,$tile);
        }
        catch (MissingCoalException $e){
            //throw $e;
            
            //Go to another state to manage selection of coals color :
            $this->gamestate->nextState( 'chooseCoal' );
        }
    }
}
