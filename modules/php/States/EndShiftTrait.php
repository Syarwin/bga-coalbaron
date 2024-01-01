<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Helpers\ScoringMajority;
use COAL\Managers\Cards;
use COAL\Managers\Players;
use COAL\Managers\Tiles;

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

  /**
   * END A SHIFT Scoring, RULES p.15
   */
  function computeEndShiftScoring($shift){
    self::trace("computeEndShiftScoring($shift)...");
    $players = Players::getAll();

    $deliveredOrders = Cards::getInLocation(CARD_LOCATION_DELIVERED);
    Notifications::endShiftDeliveries($deliveredOrders);
    /* In shift 3, deliveries are not required for scoring empty minecarts
    if(count($deliveredOrders) == 0){
      Notifications::noDeliveries();
      return;
    };*/
    $playersTiles = Tiles::getPlayersTiles();

    //We use a class to manage list elements of majorities
    $majorities = array(
      YELLOW_COAL => new ScoringMajority(1,YELLOW_COAL, 2,1),
      BROWN_COAL  => new ScoringMajority(2,BROWN_COAL,  3,1),
      GREY_COAL   => new ScoringMajority(3,GREY_COAL,   4,2),
      BLACK_COAL  => new ScoringMajority(4,BLACK_COAL,  5,2),
      TRANSPORT_BARROW    => new ScoringMajority(5,TRANSPORT_BARROW  ,6,3),
      TRANSPORT_CARRIAGE  => new ScoringMajority(6,TRANSPORT_CARRIAGE,7,3),
      TRANSPORT_MOTORCAR  => new ScoringMajority(7,TRANSPORT_MOTORCAR,8,4),
      TRANSPORT_ENGINE    => new ScoringMajority(8,TRANSPORT_ENGINE  ,9,4),
      MINECART_YELLOW => new ScoringMajority(9,MINECART_YELLOW  ,10,5),
      MINECART_BROWN  => new ScoringMajority(10,MINECART_BROWN  ,11,5),
      MINECART_GREY   => new ScoringMajority(11,MINECART_GREY   ,12,6),
      MINECART_BLACK  => new ScoringMajority(12,MINECART_BLACK  ,13,6),
    );

    $this->computeMajorities($majorities,$players,$deliveredOrders,$playersTiles);
    //Notifications::message('debug data $majorities : '.json_encode($majorities));
    
    foreach($majorities as $majority){
      if($shift >= ($majority->elementTypeIndex / 4) ){//4 new majorities per shift
        $majority->doScore($players);
      }
    }
  }
  
  function computeMajorities(&$majorities,$players,$deliveredOrders,$playersTiles){
    self::trace("computeMajorities()...");
    
    $playerCounts = array();
    foreach($players as $pId => $player){
      $playerCounts[$pId] = array();
      $playerDeliveredOrders = $deliveredOrders->filter(function ($card) use ($pId) {
        return $card->getPId() == $pId;
      });
      /* In shift 3, deliveries are not required for scoring empty minecarts
      if(count($playerDeliveredOrders) == 0){
        Notifications::noPlayerDeliveries($player);
        continue;//go to next player
      };
      */
      $spotsCounts = array(
        YELLOW_COAL => 0, 
        BROWN_COAL => 0, 
        GREY_COAL => 0, 
        BLACK_COAL => 0,
        TRANSPORT_BARROW    => 0,
        TRANSPORT_CARRIAGE  => 0,
        TRANSPORT_MOTORCAR  => 0,
        TRANSPORT_ENGINE    => 0,
        MINECART_YELLOW  => 0,
        MINECART_BROWN   => 0,
        MINECART_GREY    => 0,
        MINECART_BLACK   => 0,
      );

      $spotsCounts = $playerDeliveredOrders->reduce(function ($carry, $card){
        $counter = array_count_values($card->getCoals());
        foreach($counter as $color => $nbr){
          $carry[$color] += $nbr;
        }
        $transportCounter = count($card->getCoals());
        $carry[$card->getTransport()] += $transportCounter;
        return $carry;
      }, $spotsCounts);
      
      $playerTiles = $playersTiles->filter(function ($tile) use ($pId) {
        return $tile->getPId() == $pId;
      });
      
      $spotsCounts = $playerTiles->reduce(function ($carry, $tile){
        $carry[$tile->getMinecartColor()] += $tile->countEmptyMinecarts();
        return $carry;
      }, $spotsCounts);
      $spotsCounts = Tiles::getPlayersBaseTiles($pId)->reduce(function ($carry, $tile){
        $carry[$tile->color] += $tile->countEmptyMinecarts();
        return $carry;
      }, $spotsCounts);
      
      $playerCounts[$pId] = $spotsCounts;
      
      foreach($playerCounts[$pId] as $kind => $nbr){
        //Of course, players can only score victory points for elements of which they have at least 1 delivered order:
        if($nbr == 0) continue;

        $majorities[$kind]->studyPlayer($pId,$nbr);
      }
    }
    //Notifications::message("debug data playerCounts : ".json_encode($playerCounts));
  }

}
