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

use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Core\Globals;
use COAL\Core\Preferences;
use COAL\Core\Stats;

class CoalBaron extends Bga\GameFramework\Table
{
  use COAL\DebugTrait;
  use COAL\States\SetupTrait;
  use COAL\States\DraftTrait;
  use COAL\States\NextPlayerTrait;
  use COAL\States\NewShiftTrait;
  use COAL\States\PlaceWorkerTrait;
  use COAL\States\ChooseCoalTrait;
  use COAL\States\EndGameScoringTrait;
  use COAL\States\EndShiftTrait;
  use COAL\States\ConfirmUndoTrait;

  public static $instance = null;
  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([
      'logging' => 10,
      //game options :
      'option_visibility' => OPTION_CARDS_VISIBILITY,
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
    $pId = $this->getCurrentPId();
    $nbPlayers = Players::count();
    return [
      'prefs' => Preferences::getUiData($pId),
      'optionCardsVisibility' => Globals::getCardsVisibility(),
      'players' => Players::getUiData($pId),
      'shift' => Globals::getShift(),
      'meeples' => Meeples::getUiData(),
      'coalsInReserve' => Meeples::countAvailableCoalsColorArray(),
      'cards' => Cards::getUiData($pId),
      'cardsDeckSize' => Cards::getDeckSize(),
      'tiles' => Tiles::getUiData(),
      'tilesDeckSize' => Tiles::getDeckSize(),
      'firstPlayer' => Globals::getFirstPlayer(),
      'nbrWorkersNeeded' => $this->getSpacesNeededWorkers($nbPlayers),
      'majorities' => Globals::getAllMajorityWinners(),
    ];
  }

  /*
   * getGameProgression:
   * GAME SHIFTS's Progression : 0/3 or 1/3 or 2/3
   *      + 1/3 x (
   *   Progression in current shift = (nbWorkersTOTAL - nbWorkersInReserve) / nbWorkersTOTAL
   *      )
   */
  function getGameProgression()
  {
    $shift = Globals::getShift(); //FROM 1 to SHIFT_MAX
    $nbWorkersInReserve = Meeples::countWorkers(SPACE_RESERVE);
    $nbWorkersTOTAL = Meeples::countWorkers();
    $currentShiftProgression = ($nbWorkersTOTAL - $nbWorkersInReserve) / $nbWorkersTOTAL;
    $progress = ($shift - 1) / SHIFT_MAX + 1 / SHIFT_MAX * $currentShiftProgression;
    //die("debug getGameProgression : progress = ($shift-1)/SHIFT_MAX + 1/SHIFT_MAX * $currentShiftProgression => $progress ");
    return $progress * 100;
  }

  function actChangePreference($pref, $value)
  {
    Preferences::set($this->getCurrentPId(), $pref, $value);
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

    if ($state['type'] === "activeplayer") {
      switch ($statename) {
          default:
            $this->gamestate->nextState( "zombiePass" );
            break;
      }
      return;
    }

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
  public function getCurrentPId()
  {
    return $this->getCurrentPlayerId();
  }

  // Exposing protected method translation
  public function translate($text)
  {
    return $this->_($text);
  }
}
