<?php
namespace COAL;
use COAL\Core\Globals;
use COAL\Core\Game;
use COAL\Core\Notifications;
use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Helpers\Utils;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Models\CoalCube;

trait DebugTrait
{
  function testGoToNextPlayer()
  {
    $this->gamestate->nextState( 'next' );
  }
  function testGoToDraft()
  {
    $this->gamestate->jumpToState(ST_DRAFT_INIT);
  }
  
  function testGoToPlaceWorkers()
  {
    Globals::setShift(1);
    $this->gamestate->jumpToState(ST_PLACE_WORKER);
  }

  function testSimulateDraft()
  {
    $players = Players::getAll();
    //RESET cards to deck
    Cards::moveAllInLocation(CARD_LOCATION_OUTSTANDING, CARD_LOCATION_DECK);
    Cards::moveAllInLocation(SPACE_ORDER_1, CARD_LOCATION_DECK);
    Cards::moveAllInLocation(SPACE_ORDER_2, CARD_LOCATION_DECK);
    Cards::moveAllInLocation(SPACE_ORDER_3, CARD_LOCATION_DECK);
    Cards::moveAllInLocation(SPACE_ORDER_4, CARD_LOCATION_DECK);

    foreach($players as $player){
      for($k =0; $k< CARDS_START_NB;$k++){
        $card = Cards::getTopOf(CARD_LOCATION_DECK);
        Cards::giveCardTo($player,$card);
      }
    }

    //Cards::refillOrderSpace(SPACE_ORDER_1);
    Cards::refillOrderSpace(SPACE_ORDER_2);
    Cards::refillOrderSpace(SPACE_ORDER_3);
    Cards::refillOrderSpace(SPACE_ORDER_4);
  }

  //notif possible worker spaces : less useful now because the args send it now
  function testPWS()
  {
    $this->testReplaceMeeplesInReserve();
    Notifications::message("START DEBUG TEST 1");

    $pId = Players::getCurrentId();
    $nbPlayers = 2;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers);
    Notifications::possibleWorkerSpaces($pId,$spaces);

    Notifications::message("END DEBUG TEST 1");
    
    Notifications::message("START DEBUG TEST 2");

    $nbPlayers = 3;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers);
    Notifications::possibleWorkerSpaces($pId,$spaces);

    Notifications::message("END DEBUG TEST 2");
    
    Notifications::message("START DEBUG TEST 3");

    $nbPlayers = 4;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers);
    Notifications::possibleWorkerSpaces($pId,$spaces);

    Notifications::message("END DEBUG TEST 3");
  }
  
  function testBank()
  {
    $this->testReplaceMeeplesInReserve();

    $player = Players::getCurrent();
    self::dump("testBank() : player",$player);
    $money = $player->getMoney();

    Notifications::message("START DEBUG TEST with player money $money");
    self::placeWorkerInBank($player, SPACE_BANK_4);

    $player = Players::getCurrent();
    $money = $player->getMoney();
    Notifications::message("START DEBUG TEST Place in Bank 6 with player money $money");
    self::placeWorkerInBank($player, SPACE_BANK_6);
    
    $player = Players::getCurrent();
    $money = $player->getMoney();
    Notifications::message("START DEBUG TEST Place in Bank 4 more workers with player money $money");
    self::placeWorkerInBank($player, SPACE_BANK_4);
    
    Notifications::message("START DEBUG TEST Place in Bank 1");
    self::placeWorkerInBank($player, SPACE_BANK_1);
    
    $player = Players::getCurrent();
    $money = $player->getMoney();
    Notifications::message("END DEBUG TEST with player money $money");
  }
  
  function testActionBank()
  {
    $this->testReplaceMeeplesInReserve();
    $this->actPlaceWorker(SPACE_BANK_4);
  }

  function testMultiplesActionBank()
  {
    $this->testReplaceMeeplesInReserve();

    //MULTIPLES POSSIBLES without the last line "next state" in action actPlaceWorker
    //KO in 2 PLAYERS game
    //$this->actPlaceWorker(SPACE_BANK_3);
    $this->actPlaceWorker(SPACE_BANK_4);
    //KO in 2 PLAYERS game
    //$this->actPlaceWorker(SPACE_BANK_5);
    $this->actPlaceWorker(SPACE_BANK_6);
    $this->actPlaceWorker(SPACE_BANK_6);
    
    $this->actPlaceWorker(SPACE_BANK_4);
    $this->actPlaceWorker(SPACE_BANK_1);
    
    //$this->actPlaceWorker("FAKE_BANK_SPACE");
  }
  
  function testActionFactory()
  {
    $this->testReplaceMeeplesInReserve();
    $this->actPlaceWorker(SPACE_FACTORY_1);
  }

  function testActionOrder()
  {
    $this->testReplaceMeeplesInReserve();
    $this->actPlaceWorker(SPACE_ORDER_2);
  }

  function testActionMining()
  {
    $this->testReplaceMeeplesInReserve();
    $this->actPlaceWorker(SPACE_MINING_6);
  }
  
  function testActionMovePitCage()
  {
    //RESET for the test :
    $player = Players::getCurrent();
    $player->setCageLevel(LEVEL_SURFACE);
    Globals::setMiningMoves(6);

    $this->actMovePitCage(1);
    $this->actMovePitCage(2);
    $this->actMovePitCage(1);
    $this->actMovePitCage(LEVEL_TUNNEL_MAX);
    $this->actMovePitCage(1);
    $this->actMovePitCage(2);
  }
  
  function testActionMoveCoal()
  {
    //RESET for the test :
    $player = Players::getCurrent();
    $player->setCageLevel(LEVEL_SURFACE);
    Globals::setMiningMoves(6);
    $coals = Meeples::getPlayerCoals($player->getId());
    $coalIds = array( );
    foreach($coals as $coal){
      //if($coal->getLocation() == ( SPACE_PIT_TILE . '_1_1' )  ){
        //ALWAYS TAKE FIRST
        $coalIds[] = $coal->getId();
        $coal->setLocation(SPACE_PIT_TILE . '_1_1');
        break;
      //}
    }

    $this->actMovePitCage(1);
    $this->actMoveCoals($coalIds,SPACE_PIT_CAGE);
    $this->actMovePitCage(LEVEL_SURFACE);
    $this->actMoveCoals($coalIds,COAL_LOCATION_STORAGE); 
  }
  
  function testPossibleSpacesInDelivery()
  {
    $player = Players::getCurrent();
    $pId = $player->getId();
    self::dump("testPossibleSpacesInDelivery",$this->getPossibleSpacesInDelivery($pId ));
  }

  /* Replace meeples in reserve : */
  function testReplaceMeeplesInReserve()
  {
    $player = Players::getCurrent();
    $pId = $player->getId();
    $workers = Meeples::getAll();
    $workersIds = array();
    foreach ($workers as $worker) {
      if($worker->getType() == WORKER && $worker->getPId() == $pId ) $workersIds[] = $worker->getId();
    }
    Meeples::move($workersIds,SPACE_RESERVE );
    $nbAv = count($workersIds);
    Notifications::message("$nbAv workers are available");
  }

  function testSimulateDeliveries()
  {
    //SIMULATE DELIVERIES :
    Cards::moveAllInLocation(CARD_LOCATION_DELIVERED, CARD_LOCATION_DECK);
    Cards::shuffle(CARD_LOCATION_DECK);
    $this->testSimulateDraft();
    Cards::moveAllInLocation(CARD_LOCATION_OUTSTANDING, CARD_LOCATION_DELIVERED);
  }
  function testEndShiftScoring()
  {
    //COMMENT NExt line to test the same deliveries
    //$this->testSimulateDeliveries();
    
    //SIMULATE MOVING certain COALS :
    $player = Players::getCurrent();
    $coals = Meeples::getPlayerCoals($player->getId());
    $coalIds = array( );
    foreach($coals as $coal){
      if($coal->getLocation() == ( SPACE_PIT_TILE . '_3_-1' ) 
       || str_starts_with($coal->getLocation(),COAL_LOCATION_TILE)  ){
        $coalIds[] = $coal->getId();
        $coal->setLocation("FAKE_FOR_TEST_".$coal->getLocation());
      }
    }
    Meeples::moveAllInLocation(SPACE_PIT_TILE . "_1_1","FAKE_FOR_TEST_1_1");
    Meeples::moveAllInLocation(SPACE_PIT_TILE . "_4_1","FAKE_FOR_TEST_1_4");
    
    $this->computeEndShiftScoring(3);
  }

}
