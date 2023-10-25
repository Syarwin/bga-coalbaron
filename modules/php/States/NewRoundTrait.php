<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Engine;
use COAL\Core\Stats;
use COAL\Managers\Players;

use COAL\Managers\Tiles;
use COAL\Managers\Cards;

trait NewRoundTrait
{
  function stNextRound()
  {
    // //after 16 turns, end the game
    // if (Globals::getTurn() == 16) {
    //   $this->gamestate->nextState('game_end');
    // } else {
    //   //shuffle deckAge1 ou DeckAge2
    //   $active_deck = Globals::getTurn() < 8 ? 'DeckAge1' : 'DeckAge2';
    //   Tiles::shuffle($active_deck);

    //   //trash remaining cards and pick 4 cards per players and put it in deck1, deck2, deck3...
    //   for ($i = 1; $i <= Players::count(); $i++) {
    //     Tiles::moveAllInLocation('deck' . $i, 'trash');
    //     Tiles::pickForLocation(CARDS_PER_DECK, $active_deck, 'deck' . $i);
    //   }
    // }

    // $this->gamestate->nextState('round_start');
  }

  function stMoveAvatars()
  {

  }

}
