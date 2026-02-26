<?php

namespace COAL;

use COAL\Core\Globals;
use COAL\Core\Game;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Helpers\Log;
use COAL\Helpers\QueryBuilder;
use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Helpers\Utils;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Models\CoalCube;

trait DebugTrait
{
  
   /**
   * STUDIO : Get the database matching a bug report (when not empty)
   */
  public function loadBugReportSQL(int $reportId, array $studioPlayersIds): void {
    $this->trace("loadBugReportSQL($reportId, ".json_encode($studioPlayersIds));
    $players = $this->getObjectListFromDb('SELECT player_id FROM player', true);
  
    $sql = [];
    //This table is modified with boilerplate
    $sql[] = "ALTER TABLE `gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;";

    // Change for your game
    // We are setting the current state to match the start of a player's turn if it's already game over
    $state = ST_PLACE_WORKER;
    $sql[] = "UPDATE global SET global_value=$state WHERE global_id=1 AND global_value=99";
    foreach ($players as $index => $pId) {
      $studioPlayer = $studioPlayersIds[$index];
  
      // All games can keep this SQL
      $sql[] = "UPDATE player SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE global SET global_value=$studioPlayer WHERE global_value=$pId";
      $sql[] = "UPDATE stats SET stats_player_id=$studioPlayer WHERE stats_player_id=$pId";
  
      // Add game-specific SQL update the tables for your game
      $sql[] = "UPDATE meeples SET player_id=$studioPlayer WHERE player_id = $pId";
      $sql[] = "UPDATE cards SET player_id=$studioPlayer WHERE player_id = $pId";
      $sql[] = "UPDATE tiles SET player_id=$studioPlayer WHERE player_id = $pId";
      $sql[] = "UPDATE global_variables SET `value` = REPLACE(`value`,'$pId','$studioPlayer')";
      
      $sql[] = "UPDATE user_preferences SET player_id=$studioPlayer WHERE player_id = $pId";
    }
  
    foreach ($sql as $q) {
      $this->DbQuery($q);
    }
  
    $this->reloadPlayersBasicInfos();
  }

  /**
   * Clean everything to go to game setup, and saves space on server (don't recreate new tables)
   */
  function debug_Setup(){
    $player = Players::getCurrent();
    Game::get()->trace("debug_Setup - START ////////////////////////////////////////////////////");
    $this->debug_ClearLogs();
    Log::disable();
    $options = [
      "DEBUG_SETUP"=> true, 
      OPTION_CARDS_VISIBILITY => OPTION_VISIBLE_ALL,
     ];
    $players = self::loadPlayersBasicInfos();
    
    Stats::DB()->delete()->run();
    Cards::DB()->delete()->run();
    Meeples::DB()->delete()->run();
    Globals::DB()->delete()->run();
    Notifications::refreshUI($this->getAllDatas());

    Players::DB()->delete()->run();
    Game::get()->setupNewGame($players,$options);

    Log::enable();

    $players = self::loadPlayersBasicInfos();
    Notifications::refreshUI($this->getAllDatas());

    $this->addCheckpoint(ST_DRAFT_INIT);
    $this->gamestate->jumpToState(ST_DRAFT_INIT);
    
    Game::get()->trace("debug_Setup - END ////////////////////////////////////////////////////");
  }
  /*
  function testGoToNextPlayer()
  {
    $this->gamestate->nextState('next');
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

  function testGoNextShift()
  {
    $this->gamestate->jumpToState(ST_NEXT_SHIFT);
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

    foreach ($players as $player) {
      for ($k = 0; $k < CARDS_START_NB; $k++) {
        $card = Cards::getTopOf(CARD_LOCATION_DECK);
        Cards::giveCardTo($player, $card);
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

    $player = Players::getCurrent();
    $pId = $player->getId();
    $money = $player->getMoney();
    $nbPlayers = 2;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers, $money);
    Notifications::possibleWorkerSpaces($pId, $spaces);

    Notifications::message("END DEBUG TEST 1");

    Notifications::message("START DEBUG TEST 2");

    $nbPlayers = 3;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers, $money);
    Notifications::possibleWorkerSpaces($pId, $spaces);

    Notifications::message("END DEBUG TEST 2");

    Notifications::message("START DEBUG TEST 3");

    $nbPlayers = 4;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers, $money);
    Notifications::possibleWorkerSpaces($pId, $spaces);

    Notifications::message("END DEBUG TEST 3");
  }

  function testIsPWS()
  {
    $player = Players::getCurrent();
    $nbPlayers = Players::count();
    $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player);

    $spacesToTest = [
      SPACE_BANK_1, SPACE_BANK_3, SPACE_BANK_4, SPACE_BANK_5, SPACE_BANK_6,
      SPACE_FACTORY_1, SPACE_FACTORY_2, SPACE_FACTORY_3, SPACE_FACTORY_4, SPACE_FACTORY_5, SPACE_FACTORY_6, SPACE_FACTORY_7, SPACE_FACTORY_8,
      SPACE_FACTORY_DRAW,
      SPACE_ORDER_1, SPACE_ORDER_2, SPACE_ORDER_3, SPACE_ORDER_4,
      SPACE_ORDER_DRAW,
      SPACE_DELIVERY_BARROW, SPACE_DELIVERY_CARRIAGE, SPACE_DELIVERY_MOTORCAR, SPACE_DELIVERY_ENGINE,
      SPACE_MINING_4, SPACE_MINING_6, SPACE_MINING_8, SPACE_MINING_10,
    ];

    foreach ($spacesToTest as $space) {
      $isPossible = $this->isPossibleSpace($player->getId(), $nbPlayers, $space, $player->getMoney(), $nbAvailableWorkers);
      Notifications::messageTo($player, "Possible space $space ? " . ($isPossible ? 'true' : 'false'), []);
    }
  }

  function testBank()
  {
    $this->testReplaceMeeplesInReserve();

    $player = Players::getCurrent();
    self::dump("testBank() : player", $player);
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

  function testMissingCoals()
  {
    Meeples::moveAllByType(YELLOW_COAL, "FAKE_SPACE");
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
    $this->actStopMining();
  }

  function testActionMoveCoal()
  {
    //RESET for the test :
    $player = Players::getCurrent();
    $player->setCageLevel(LEVEL_SURFACE);
    Globals::setMiningMoves(6);
    $coals = Meeples::getPlayerCoals($player->getId());
    $coalIds = array();
    foreach ($coals as $coal) {
      //if($coal->getLocation() == ( SPACE_PIT_TILE . '_1_1' )  ){
      //ALWAYS TAKE FIRST
      $coalIds[] = $coal->getId();
      $coal->setLocation(SPACE_PIT_TILE . '_1_1');
      break;
      //}
    }

    $this->actMovePitCage(1);
    $this->actMoveCoals($coalIds, SPACE_PIT_CAGE);
    $this->actMovePitCage(LEVEL_SURFACE);
    $this->actMoveCoals($coalIds, COAL_LOCATION_STORAGE);
  }

  function testPossibleSpacesInDelivery()
  {
    $player = Players::getCurrent();
    $pId = $player->getId();
    self::dump("testPossibleSpacesInDelivery", $this->getPossibleSpacesInDelivery($pId));
  }

  // Replace meeples in reserve :
  function testReplaceMeeplesInReserve()
  {
    $player = Players::getCurrent();
    $pId = $player->getId();
    $workers = Meeples::getAll();
    $workersIds = array();
    foreach ($workers as $worker) {
      if ($worker->getType() == WORKER && $worker->getPId() == $pId) $workersIds[] = $worker->getId();
    }
    Meeples::move($workersIds, SPACE_RESERVE);
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
  function debug_EndShiftScoring()
  {
    //COMMENT NExt line to test the same deliveries
    //$this->testSimulateDeliveries();
    //
    ////SIMULATE MOVING certain COALS :
    //$player = Players::getCurrent();
    //$coals = Meeples::getPlayerCoals($player->getId());
    //$coalIds = array( );
    //foreach($coals as $coal){
    //  if($coal->getLocation() == ( SPACE_PIT_TILE . '_3_-1' ) 
    //   || str_starts_with($coal->getLocation(),COAL_LOCATION_TILE)  ){
    //    $coalIds[] = $coal->getId();
    //    $coal->setLocation("FAKE_FOR_TEST_".$coal->getLocation());
    //  }
    //}
    //Meeples::moveAllInLocation(SPACE_PIT_TILE . "_1_1","FAKE_FOR_TEST_1_1");
    //Meeples::moveAllInLocation(SPACE_PIT_TILE . "_4_1","FAKE_FOR_TEST_1_4");
    //
    $this->computeEndShiftScoring(3);
  }


  function testSMW()
  {
    Globals::initAllMajorityWinners();
    $gameRound = 2;
    $reward = 5;
    $typeIndex = 2;
    $pIds = Players::getAll()->getIds();
    Globals::saveMajorityWinners($pIds, $typeIndex, $gameRound, 1);
  }

  function testEndGameScoring()
  {
    $this->computeEndGameScoring();
  }

  function testEmptyMinecarts()
  {
    $player = Players::getCurrent();
    $player->money = 55;

    //Test about like id% => test 1 and 11
    $tilesToTest = [1,11,12,13];
    $tilesToEmpty = [11,13];
    foreach($tilesToTest as $tileId){
      $tile = Tiles::get($tileId);
      $this->giveTileToPlayer($player, $tile);
      if(in_array($tileId,$tilesToEmpty)){
        $filledCoals = Meeples::getPlayerTileCoals($player->id, $tile->getId())->getIds();
        Meeples::move($filledCoals, SPACE_RESERVE);
      }
    }
    
    $tiles = Tiles::getPlayersTiles();
    foreach($tiles as $tileId => $tile){
      $filledCoals = Meeples::getPlayerTileCoals($player->id, $tile->getId())->count();
      $nbEmpty = $tile->countEmptyMinecarts();
      $max = $tile->getNumber();
      Notifications::message("testEmptyMinecarts tile $tileId : $nbEmpty / $max ($filledCoals filled)");
    }

    $this->testRefreshUI();
  }
  */
  
  //----------------------------------------------------------------
  function testRefreshUI(){
    Notifications::refreshUI($this->getAllDatas());
  }
  
  //Clear logs
  function debug_ClearLogs(){
    $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');
    $query->delete()->run();
  }
  
  /**
   * Another example of debug function, to easily test the zombie code.
   */
  public function debug_playOneMove(int $nbMoves = 1) {
      $this->debug->playUntil(fn(int $count) => $count == $nbMoves);
  }

}
