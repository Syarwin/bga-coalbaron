<?php

namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Engine;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Managers\Meeples;
use COAL\Managers\Players;
use COAL\Helpers\Log;
use COAL\Models\Player;

trait NextPlayerTrait
{

  function stNextPlayer()
  {
    self::trace("stNextPlayer()");
    //END ROUND CoNDITIONS
    $nbAvailableWorkers = Meeples::getNbAvailableWorkers();
    if ($nbAvailableWorkers == 0) {
      $this->gamestate->nextState('end_shift');
      return;
    }

    //Active next player WITH WORKERS only ! and not zombie :
    $activePlayer = Players::getActive();
    $player_id = $activePlayer->id;
    $nbPlayers = Players::count();
    $k = 0;
    $nextPlayer = null;
    do {
      $k++;
      //! activeNextPlayer active zombies also !
      //$player_id = self::activeNextPlayer();
      $player_id = Players::getNextId($player_id);
      $player = Players::get($player_id);
      if($player->getZombie() == 1){
        continue;
      }
      $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player_id);
      if ($nbAvailableWorkers == 0) {
        Notifications::skipTurn($player);
      } else {
        $nextPlayer = $player;
      }
    } while (!isset($nextPlayer) && $k<=$nbPlayers && isset($player_id));
    
    if(!isset($nextPlayer) ){
      //PREMATURE END IF only ZOMBIES left
      $this->gamestate->nextState('end_shift');
      return;
    }

    Players::changeActive($nextPlayer->id);
    self::giveExtraTime($nextPlayer->id);

    $this->addCheckpoint(ST_PLACE_WORKER);
    $this->gamestate->nextState('next');
  }

  function stConfirmChoices()
  {
    $this->gamestate->nextState('');
  }
}
