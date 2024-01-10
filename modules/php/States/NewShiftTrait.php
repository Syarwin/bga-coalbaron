<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Managers\Meeples;
use COAL\Managers\Players;

trait NewShiftTrait
{
  function stNextShift()
  {
    //after 3 rounds, end the game
    if (Globals::getShift() == SHIFT_MAX) {
      $this->gamestate->nextState('game_end');
    } else {
      Globals::incShift(1);
      $firstPlayerId = Globals::getFirstPlayer();
      
      if (Globals::getShift() > 1) {
        // CHANGE starting player

        $playersMajorityInFactory = Meeples::getPlayerMajorityInFactory();
        self::dump("stNextShift()... playersMajorityInFactory after $firstPlayerId",$playersMajorityInFactory);
        if(count($playersMajorityInFactory) == 1){
          /* GAME RULE :
          Give the starting player marker to the
          player who has the most workers on worker
          spaces in the minecarts factory.
          */
          $newFirstPlayerId = $playersMajorityInFactory[0];
        }
        else {
          /* GAME RULE :
          In the case of a tie, give the starting player
          marker to the tied player that in clockwise
          direction is sitting closest to the starting
          player of the previous shift.
          (If the starting player of the previous shift
          participates in the tie, he does not remain
          the starting player.)
          */
          $turnOrder = Players::getTurnOrder($firstPlayerId);
          self::dump("stNextShift()... turnOrder after $firstPlayerId ",$turnOrder);
          foreach($turnOrder as $orderPId){
            if($orderPId == $firstPlayerId ) continue;
            if(count($playersMajorityInFactory) == 0 || array_search($orderPId,$playersMajorityInFactory) !== FALSE){
              //next player to play after current first player AND NO ONE in factory majority tie  
              //next player to play after current first player AND in factory majority tie  
              $newFirstPlayerId = $orderPId;
              break;
            }
          }
        }
        if(!isset($newFirstPlayerId)){
          throw new \feException('stNextShift(): next starting player have not been found !');
        }
        Globals::setFirstPlayer($newFirstPlayerId);
      }
      $player_id = Globals::getFirstPlayer();
      $shift = Globals::getShift();

      Notifications::newShift($shift);
      Players::changeActive( $player_id );
      $player = Players::get($player_id);
      Notifications::updateFirstPlayer($player);
      //Stat turnOrder :
      $turnOrder = Players::getTurnOrder($player_id);
      foreach($turnOrder as $key => $orderPId){ 
        $setterStatName = "setTurnOrder$shift";
        Stats::$setterStatName( $orderPId, $key + 1);
      }

      //UNREVEAL delivered orders : only UI effect

      //SEND all workers to SUPPLY
      Meeples::moveAllByType(WORKER,SPACE_RESERVE);

      $this->gamestate->nextState('shift_start');
    }
  }

}
