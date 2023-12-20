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

trait DebugTrait
{

  function testSimulateDraft()
  {
    $players = Players::getAll();
    //RESET cards to deck
    Cards::moveAllInLocation(CARD_LOCATION_OUTSTANDING, CARD_LOCATION_DECK);

    foreach($players as $player){
      for($k =0; $k< CARDS_START_NB;$k++){
        $card = Cards::getTopOf(CARD_LOCATION_DECK);
        Cards::giveCardTo($player,$card);
      }
    }
  }

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

  /* Replace meeples in reserve : */
  function testReplaceMeeplesInReserve()
  {
    $player = Players::getCurrent();
    $pId = $player->getId();
    $workers = Meeples::getAll();
    $workersIds = array();
    foreach ($workers as $worker) {
      if($worker['type'] == WORKER && $worker['player_id'] == $pId ) $workersIds[] = $worker['meeple_id'];
    }
    Meeples::move($workersIds,SPACE_RESERVE );
    $nbAv = count($workersIds);
    Notifications::message("$nbAv workers are available");
  }

}
