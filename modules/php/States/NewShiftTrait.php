<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Notifications;
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
        self::dump("stNextShift()... playersMajorityInFactory ",$playersMajorityInFactory);
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
          foreach($turnOrder as $orderPId){
            if($orderPId == $firstPlayerId ) continue;
            if(array_search($orderPId,$playersMajorityInFactory) !== FALSE){
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

      Notifications::newShift(Globals::getShift());
      Players::changeActive( $player_id );
      $player = Players::get($player_id);
      Notifications::updateFirstPlayer($player);

      //UNREVEAL delivered orders : only UI effect

      //SEND all workers to SUPPLY
      Meeples::moveAllByType(WORKER,SPACE_RESERVE);

      $this->gamestate->nextState('shift_start');
    }
  }

}
