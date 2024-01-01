<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Managers\Cards;
use COAL\Managers\Players;

trait EndShiftTrait
{
  function stEndShift()
  {
    self::trace("stEndShift()...");
    $shift = Globals::getShift();
    Notifications::endShift($shift);
    $this->computeEndShiftScoring($shift);
    $this->gamestate->nextState( 'next' );
  }

  function computeEndShiftScoring($shift){
    self::trace("computeEndShiftScoring($shift)...");
    $players = Players::getAll();

    $deliveredOrders = Cards::getInLocation(CARD_LOCATION_DELIVERED);
    Notifications::endShiftDeliveries($deliveredOrders);
    if(count($deliveredOrders) == 0){
      Notifications::noDeliveries();
      return;
    };

    //We use a class to manage list of majorities
    $majorities = array(
      YELLOW_COAL => new \COAL\Helpers\ScoringMajority(1,YELLOW_COAL, 2,1),
      BROWN_COAL  => new \COAL\Helpers\ScoringMajority(2,BROWN_COAL,  3,1),
      GREY_COAL   => new \COAL\Helpers\ScoringMajority(3,GREY_COAL,   4,2),
      BLACK_COAL  => new \COAL\Helpers\ScoringMajority(4,BLACK_COAL,  5,2),
      TRANSPORT_BARROW    => new \COAL\Helpers\ScoringMajority(5,TRANSPORT_BARROW  ,6,3),
      TRANSPORT_CARRIAGE  => new \COAL\Helpers\ScoringMajority(6,TRANSPORT_CARRIAGE,7,3),
      TRANSPORT_MOTORCAR  => new \COAL\Helpers\ScoringMajority(7,TRANSPORT_MOTORCAR,8,4),
      TRANSPORT_ENGINE    => new \COAL\Helpers\ScoringMajority(8,TRANSPORT_ENGINE  ,9,4),
      //TODO JSA OTHER TYPES
    );

    $this->computeMajorities($majorities,$players,$deliveredOrders);
    //Notifications::message('debug data $majorities : '.json_encode($majorities));
    
    foreach($majorities as $majority){
      if($shift >= ($majority->elementTypeIndex / 4) ){//4 new majorities per shift
        $majority->doScore($players);
      }
    }
  }
  
  function computeMajorities(&$majorities,$players,$deliveredOrders){
    self::trace("computeMajorities()...");
    
    $playerCounts = array();
    foreach($players as $pId => $player){
      $playerCounts[$pId] = array();
      $playerDeliveredOrders = $deliveredOrders->filter(function ($card) use ($pId) {
        return $card->getPId() == $pId;
      });
      if(count($playerDeliveredOrders) == 0){
        Notifications::noPlayerDeliveries($player);
        continue;//go to next player
      };
      $spotsCounts = array(
        YELLOW_COAL => 0, 
        BROWN_COAL => 0, 
        GREY_COAL => 0, 
        BLACK_COAL => 0,
        TRANSPORT_BARROW    => 0,
        TRANSPORT_CARRIAGE  => 0,
        TRANSPORT_MOTORCAR  => 0,
        TRANSPORT_ENGINE    => 0,
      );

      $playerCounts[$pId] = $playerDeliveredOrders->reduce(function ($carry, $card){
        $counter = array_count_values($card->getCoals());
        foreach($counter as $color => $nbr){
          $carry[$color] += $nbr;
        }
        $transportCounter = count($card->getCoals());
        $carry[$card->getTransport()] += $transportCounter;
        return $carry;
      }, $spotsCounts);
      
      foreach($playerCounts[$pId] as $kind => $nbr){
        //Of course, players can only score victory points for elements of which they have at least 1 delivered order:
        if($nbr == 0) continue;

        $majorities[$kind]->studyPlayer($pId,$nbr);
      }
    }
    //Notifications::message("debug data playerCounts : ".json_encode($playerCounts));
  }

}
