<?php

namespace COAL\Managers;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Helpers\Utils;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */

class Players extends \COAL\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \COAL\Models\Player($row);
  }

  public function setupNewGame($players, $options)
  {
    // Create players
    $gameInfos = Game::get()->getGameinfos();
    $colors = $gameInfos['player_colors'];
    $query = self::DB()->multipleInsert(['player_id', 'player_color', 'player_canal', 'player_name', 'player_avatar', 'money', 'player_score_aux']);

    $values = [];
    $initialMoney = Players::getInitialMoney(count($players));
    foreach ($players as $pId => $player) {
      $color = array_shift($colors);
      $values[] = [$pId, $color, $player['player_canal'], $player['player_name'], $player['player_avatar'], $initialMoney, $initialMoney];
    }
    $query->values($values);

    Game::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);
    Game::get()->reloadPlayersBasicInfos();
    /* MOVED, to be used with modified Stats module
    foreach ($players as $pId => $player) {
      Stats::inc( "moneyReceived", $pId, $initialMoney );
      Stats::inc( "moneyLeft", $pId, $initialMoney );
    }
    */
  }

  public static function getInitialMoney($nbPlayers){
    return 12 - $nbPlayers;
  }

  public function getActiveId()
  {
    return Game::get()->getActivePlayerId();
  }

  public function getCurrentId()
  {
    return (int) Game::get()->getCurrentPId();
  }

  public function getAll()
  {
    return self::DB()->get(false);
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()
      ->where($pId)
      ->getSingle();
  }

  public static function getActive()
  {
    return self::get();
  }

  public static function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public function getNextId($player = null)
  {
    $player = $player ?? Players::getCurrent();
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }

  /*
   * Return the number of players
   */
  public static function count()
  {
    return self::DB()->count();
  }

  /*
   * getUiData : get all ui data of all players
   */
  public function getUiData($pId)
  {
    return self::getAll()
      ->map(function ($player) use ($pId) {
        return $player->getUiData($pId);
      })
      ->toAssoc();
  }

  /**
   * Get current turn order according to first player variable
   */
  public static function getTurnOrder($firstPlayer = null)
  {
    $firstPlayer = $firstPlayer ?? Globals::getFirstPlayer();
    $order = [];
    $p = $firstPlayer;
    do {
      $order[] = $p;
      $p = self::getNextId($p);
    } while ($p != $firstPlayer);
    return $order;
  }

  /**
   * This allow to change active player
   */
  public static function changeActive($pId)
  {
    Game::get()->gamestate->changeActivePlayer($pId);
  }

  public static function giveMoney($player,$money){
    $pId = $player->getId();
    self::DB()->inc(['money' => $money], $pId);
    $player->incScoreAux($money);
    Notifications::giveMoney($player,$money);
    Stats::inc("moneyReceived",$player,$money);
    Stats::inc("moneyLeft",$player,$money);
  }
  
  public static function spendMoney($player,$money){
    $pId = $player->getId();
    if($player->getMoney() < $money){
      //Should not happen
      throw new \BgaVisibleSystemException("Not enough money to spend");
    }
    self::DB()->inc(['money' => 0-$money], $pId);
    $player->incScoreAux(0 - $money);
    Notifications::spendMoney($player,$money);
    Stats::inc("moneySpent",$player,$money);
    Stats::inc("moneyLeft",$player,-$money);
  }
}
