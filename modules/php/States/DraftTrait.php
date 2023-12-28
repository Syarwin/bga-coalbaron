<?php
namespace COAL\States;

use COAL\Core\Notifications;
use COAL\Managers\Cards;
use COAL\Managers\Players;

/**
 * States actions about draft cards following setup
 */
trait DraftTrait
{
  
  function stNewDraft()
  {
    $nbPlayers = Players::count();
    Notifications::startDraft();
    // draw 13/10/7 cards to board when 4/3/2 players 
    /*
    $cardsPerPlayers = [2 => 7, 3 => 10, 4 => 13];
    $number = $cardsPerPlayers[$nbPlayers];
    */
    $number = CARDS_START_NB * $nbPlayers + 1;
    Cards::drawCardsToDraft($number);

    //ACTIVATE next COUNTER CLOCKWISE player (because active player is currently the first player)
    $player_id = $this->activePrevPlayer();

    $this->gamestate->nextState('next');
  }

  function stDraftNextPlayer()
  {
    // Active previous player (COUNTER CLOCKWISE )
    $player_id = $this->activePrevPlayer();
    self::giveExtraTime( $player_id );

    //END DRAFT CONDITIONS
    $nbDraftCards = Cards::countInLocation(CARD_LOCATION_DRAFT);
    if($nbDraftCards == 1){
      $this->endDraft();
      $this->gamestate->nextState('end');
      return;
    }

    $this->gamestate->nextState('next');
  }

  function argDraft()
  {
    return array(
      'cards' => Cards::getDraft(),
    );
  }

  function actTakeCard($cardId){
    self::checkAction( 'actTakeCard' ); 
    self::trace("actTakeCard($cardId)");
    
    $card = Cards::get($cardId);

    //ANTICHEAT :
    if($card->getLocation() != CARD_LOCATION_DRAFT){
      throw new \BgaVisibleSystemException("Card $cardId is not selectable");
    }

    $player = Players::getActive();
    Cards::giveCardTo($player,$card);

    $this->gamestate->nextState('next');
  }
  
  function endDraft()
  { 
    $lastCard = Cards::pickOneForLocation(CARD_LOCATION_DRAFT,SPACE_ORDER_4,0,false);
    Notifications::endDraft($lastCard);
    Cards::refillOtherOrderSpaces(SPACE_ORDER_4);
  }

}
