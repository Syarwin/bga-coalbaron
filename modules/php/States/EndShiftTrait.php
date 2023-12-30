<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Notifications;

trait EndShiftTrait
{
  function stEndShift()
  {
    $shift = Globals::getShift();
    Notifications::endShift($shift);
    //TODO JSA END SHIFT SCORING
    $this->gamestate->nextState( 'next' );
  }

}
