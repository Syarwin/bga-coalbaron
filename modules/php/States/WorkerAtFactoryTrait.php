<?php

namespace COAL\States;

use COAL\Core\Notifications;
use COAL\Managers\Meeples;
use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Models\TileCard;

/*
functions about workers at the Minecarts Factory spaces
*/
trait WorkerAtFactoryTrait
{
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Factory"
     */
    function getPossibleSpacesInFactory($pId) {
        $spaces = Tiles::getPossibleSpacesInFactory();
        //TODO JSA FILTER on available money VS cost 
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
        $tile->moveToPlayerBoard($player);
        //TODO JSA ADD 1 COAL in each minecart on the tile
        $newTile = Tiles::refillFactorySpace($space);
        Notifications::refillFactorySpace($newTile);
    }
}
