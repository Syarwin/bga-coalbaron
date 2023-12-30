<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Engine;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Managers\Meeples;
use COAL\Managers\Players;

trait NextPlayerTrait
{
  
  function stNextPlayer()
  {
    //END ROUND CoNDITIONS
    $nbAvailableWorkers = Meeples::getNbAvailableWorkers();
    if ($nbAvailableWorkers == 0) {
      $this->gamestate->nextState('end_shift');
      return;
    }

    //Active next player WITH WORKERS only !
    do {
      $player_id = self::activeNextPlayer();
      $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player_id);
      if($nbAvailableWorkers == 0) {
        $player = Players::get($player_id);
        Notifications::skipTurn($player);
      }
    } while ($nbAvailableWorkers == 0);

    self::giveExtraTime( $player_id );

    $this->gamestate->nextState('next');
  }

  function stConfirmChoices()
  {
    Notifications::message("TODO JSA stConfirmChoices");
    $this->gamestate->nextState('');
  }
}
