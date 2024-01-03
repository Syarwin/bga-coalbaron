<?php

namespace COAL\States;

use COAL\Core\Notifications;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Managers\Players;

/*
functions about workers at the Order Cards spaces
*/
trait WorkerAtOrderTrait
{
    
    function argChooseCard(){
        $player_id = Players::getActive()->getId();
        $privateDatas = array ();

        $cards = Cards::getInLocation(CARD_LOCATION_DRAW)->map(function ($card) {
            return $card->getUiData();
          }) ->toAssoc();

        $privateDatas[$player_id] = array(
            'cards' => $cards,
        );

        return array(
            '_private' => $privateDatas,
        );
    }

    /**
     * Action of choosing 0-1 card and returning the others in wanted order 
     * @param int $cardId Id of the card to keep (optional)
     * @param TOP|BOTTOM $returnDest (comes from action enum)
     * @param array $otherCardsOrder Order of cards ids to put on $returnDest 
     */
    function actChooseCard($cardId, $returnDest, $otherCardsOrder){
        self::checkAction( 'actChooseCard' ); 
        self::trace("actChooseCard($cardId, $returnDest)...");

        $player = Players::getActive();
        $cardsNb = 0;

        //ANTICHEATS :
        if(isset($cardId)){
            //IF player keeps a card
            $card = Cards::get($cardId);
            if($card->getLocation() != CARD_LOCATION_DRAW){
              throw new \BgaVisibleSystemException("Card $cardId is not selectable");
            }
            //$cardsNb++;
            Cards::giveCardTo($player,$card);
        }
        $cardsNb += count($otherCardsOrder);
        /*
        $expectedCount = CARDS_DRAW_NUMBER;
        IF EMPTY DECK, could be less 
        */
        $expectedCount = Cards::countInLocation(CARD_LOCATION_DRAW);
        if($cardsNb != $expectedCount)
            throw new \BgaVisibleSystemException("Wrong number of cards : $cardsNb != $expectedCount");

        if($returnDest == "TOP") {
            Cards::moveAllToTop($otherCardsOrder,CARD_LOCATION_DRAW,CARD_LOCATION_DECK);
            Notifications::returnCardsToTop($player,count($otherCardsOrder));
        } else {
            Cards::moveAllToBottom($otherCardsOrder,CARD_LOCATION_DRAW,CARD_LOCATION_DECK);
            Notifications::returnCardsToBottom($player,count($otherCardsOrder));
        }
    
        $this->gamestate->nextState('next');
    }

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
        $deckSize = Cards::countInLocation(CARD_LOCATION_DECK);
        //TODO JSA PERFS ? could be more efficient to get all distinct "order_%" location in cards in order to filter
        //FILTER EMPTY ORDERS (because deck may be empty )
        $filter = function ($space) use ($deckSize) {
            if($space == SPACE_ORDER_DRAW && $deckSize >0 ) return true;
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
     /**
     * FOLLOW THE RULES of ACTION 5 - SPECIAL CASE : drawing 5 cards
     */
    function placeWorkerInDrawOrder($player){
        self::trace("placeWorkerInDrawOrder()...");
        Meeples::placeWorkersInSpace($player,SPACE_ORDER_DRAW);

        $cards = Cards::pickForLocation(CARDS_DRAW_NUMBER,CARD_LOCATION_DECK,CARD_LOCATION_DRAW);
        
        //Go to another state to manage selection of cards :
        $this->gamestate->nextState( 'chooseCard' );
    }
}
