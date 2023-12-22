<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Engine;
use COAL\Core\Notifications;
use COAL\Core\Stats;

trait NextPlayerTrait
{
  
  function stNextPlayer()
  {
    // Active next player
    $player_id = self::activeNextPlayer();

    self::giveExtraTime( $player_id );

    //TODO JSA END ROUND CoNDITIONS

    $this->gamestate->nextState('next');
  }

  function stConfirmChoices()
  {
    Notifications::message("TODO JSA stConfirmChoices");
    $this->gamestate->nextState('');
  }
}
