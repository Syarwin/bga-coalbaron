<?php

namespace COAL\States;

use COAL\Core\Notifications;
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
            if($space == SPACE_FACTORY_DRAW) return true;
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
        Players::spendMoney($player,$tile->getCost());
        $column = Tiles::getPlayerNextColumnForTile($player->getId(),$tile);
        $tile->moveToPlayerBoard($player,$column);
        Meeples::placeCoalsOnTile($player,$tile);

        //TODO JSA refillFactorySpace only when player confirmed the turn 
        $newTile = Tiles::refillFactorySpace($space);
        Notifications::refillFactorySpace($newTile);
    }
}
