<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CoalBaron implementation : Timothée Pecatte <tim.pecatte@gmail.com> & joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * coalbaron.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

$swdNamespaceAutoload = function ($class) {
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'COAL') {
    array_shift($classParts);
    $file = dirname(__FILE__) . '/modules/php/' . implode(DIRECTORY_SEPARATOR, $classParts) . '.php';
    if (file_exists($file)) {
      require_once $file;
    } else {
      var_dump('Cannot find file : ' . $file);
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

require_once APP_GAMEMODULE_PATH . 'module/table/table.game.php';

use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Core\Globals;
use COAL\Core\Preferences;
use COAL\Core\Stats;

class CoalBaron extends Table
{
  use COAL\DebugTrait;
  use COAL\States\SetupTrait;
  use COAL\States\NextPlayerTrait;
  use COAL\States\NewRoundTrait;
  use COAL\States\PlaceWorkerTrait;
  use COAL\States\EndRoundScoringTrait;

  public static $instance = null;
  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([
      'logging' => 10,
    ]);
    Stats::checkExistence();
  }
  public static function get()
  {
    return self::$instance;
  }

  protected function getGameName()
  {
    // Used for translations and stuff. Please do not modify.
    return 'coalbaron';
  }


  /*
   * getAllDatas:
   */
  public function getAllDatas()
  {
    $pId = self::getCurrentPId();
    return [
      'prefs' => Preferences::getUiData($pId),
      'players' => Players::getUiData($pId),
      'turn' => Globals::getTurn(),
      'meeples' => Meeples::getUiData(),
      'cards' => Cards::getUiData(),
      'tiles' => Tiles::getUiData(),
      'firstPlayer' => Globals::getFirstPlayer(),
    ];
  }

  /*
   * getGameProgression:
   */
  function getGameProgression()
  {
    return 0; // TODO
    // return (Globals::getTurn() / 16) * 100;
  }

  function actChangePreference($pref, $value)
  {
    Preferences::set($this->getCurrentPId(), $pref, $value);
  }
  
  function argPlaceWorker(){
    $player = Players::getActive();
    $spaces = self::getPossibleSpaces($player->getId(), Players::count());
    $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player);

    return array(
      'turn' => Globals::getTurn(),
      'meeples' => Meeples::getUiData(),
      'cards' => Cards::getUiData(),
      'tiles' => Tiles::getUiData(),
      'spaces' => $spaces,
      'nbAvailableWorkers' => $nbAvailableWorkers,
    );
  }
  
  function argMiningSteps(){
    $player = Players::getActive();

    return array(
      'cageLevel' => $player->getCageLevel(),
      'meeples' => Meeples::getCoalsUiData($player->getId()),
      'moves' => Globals::getMiningMoves(),
    );
  }

  function actPlaceWorker($space)
  {
    self::checkAction( 'actPlaceWorker' ); 

    $player = Players::getCurrent();
    $nbPlayers = Players::count();

    // ANTICHEATS : available workers >0 + possible space
    $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player);
    if($nbAvailableWorkers == 0) 
      throw new \BgaVisibleSystemException("Not enough workers to play");
    if(! $this->isPossibleSpace($player->getId(), $nbPlayers,$space) )
      throw new \BgaVisibleSystemException("Incorrect place to place a worker : $space");

    $this->placeWorker($player,$space);
    if( ST_PLACE_WORKER == $this->gamestate->state_id()){
      //GO TO NEXT STATE ONLY IF not already changed by the previous method
      $this->gamestate->nextState( 'next' );
    }
  }

  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayerId)
  {
    $statename = $state['name'];

    switch ($statename) {
      default:
        throw new feException('Zombie mode not supported at this game state: ' . $statename);
    }
  }

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
  }

  /////////////////////////////////////////////////////////////
  // Exposing protected methods, please use at your own risk //
  /////////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId()
  {
    return self::getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text)
  {
    return self::_($text);
  }
}
