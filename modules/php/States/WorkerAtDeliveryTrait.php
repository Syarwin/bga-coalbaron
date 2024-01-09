<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Managers\Players;

/*
functions about workers at the Delivery spaces
*/
trait WorkerAtDeliveryTrait
{
    /**
     * List all Worker Spaces to play on specified action "Delivery"
     */
    function getAllSpacesInDelivery() {
        $spaces = array(
            SPACE_DELIVERY_BARROW,
            SPACE_DELIVERY_CARRIAGE,
            SPACE_DELIVERY_MOTORCAR,
            SPACE_DELIVERY_ENGINE,
        );
        return $spaces;
    }
    /**
     * List all possible Worker Spaces to play by player $pId and specified action "Delivery"
     */
    function getPossibleSpacesInDelivery($pId) {
        $spaces = $this->getAllSpacesInDelivery();

        //FILTER on player filled Orders (type)
        $filter = function ($space) use ($pId) {
            $deliveryType = $this->getDeliveryTypeFromSpace($space);
            $cards = Cards::getPlayerCompletedOrdersToDeliver($pId,$deliveryType);
            if(count($cards) ==0){
                return false;
            }
            return true;
        };

        $spaces = array_values(array_filter($spaces,$filter));
        
        return $spaces;
    }

    public function getDeliveryTypeFromSpace($space){
        switch($space){
            case SPACE_DELIVERY_BARROW: return TRANSPORT_BARROW;
            case SPACE_DELIVERY_ENGINE: return TRANSPORT_ENGINE;
            case SPACE_DELIVERY_CARRIAGE: return TRANSPORT_CARRIAGE;
            case SPACE_DELIVERY_MOTORCAR: return TRANSPORT_MOTORCAR;
            default:
                throw new \BgaVisibleSystemException("Not supported delivery type : $space");
        }
    }
    
    /**
     * FOLLOW THE RULES of ACTION 3
     */
    function placeWorkerInDelivery($player, $space){
        self::trace("placeWorkerInDelivery($space)...");
        Meeples::placeWorkersInSpace($player,$space);
        $deliveryType = $this->getDeliveryTypeFromSpace($space);
        $cards = Cards::getPlayerCompletedOrdersToDeliver($player->getId(),$deliveryType);
 
        foreach($cards as $card){
            $player->addPoints($card->getPoints());
            $card->moveToDelivered();
            Notifications::cardDelivered($player,$card);
        }

        Stats::inc( "nbActions3", $player );
    }
}
