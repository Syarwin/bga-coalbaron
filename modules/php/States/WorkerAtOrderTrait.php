<?php

namespace COAL\States;

use COAL\Core\Notifications;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;

/*
functions about workers at the Order Cards spaces
*/
trait WorkerAtOrderTrait
{
    /**
     * List all Worker Spaces to play on specified action "Order"
     */
    function getAllSpacesInOrder($nbPlayers) {
        $spaces = Cards::getUnlockedOrderSpaces($nbPlayers);
        return $spaces;
    }
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Order"
     */
    function getPossibleSpacesInOrder($pId,$nbPlayers) {
        $spaces = $this->getAllSpacesInOrder($nbPlayers);
        //TODO JSA PERFS ? could be more efficient to get all distinct "order_%" location in cards in order to filter
        //FILTER EMPTY ORDERS (because deck may be empty )
        $filter = function ($space) {
            $card = Cards::getCardInOrder($space);
            if(!isset($card)){
                return false;
            }
            return true;
        };

        $spaces = array_values(array_filter($spaces,$filter));

        return $spaces;
    }
    
    /**
     * FOLLOW THE RULES of ACTION 5 
     */
    function placeWorkerInOrder($player, $space){
        self::trace("placeWorkerInOrder($space)...");
        Meeples::placeWorkersInSpace($player,$space);
        $card = Cards::getCardInOrder($space);
        Cards::giveCardTo($player,$card);

        //TODO JSA refillOrderSpace only when player confirmed the turn 
        $newCard = Cards::refillOrderSpace($space);
        Notifications::refillOrderSpace($newCard);
    }
}
