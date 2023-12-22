<?php

namespace COAL\Core;

use COAL\Managers\Players;
use COAL\Helpers\Utils;
use COAL\Core\Globals;

class Notifications
{
  public static function newTurn($step)
  {
  }

  public static function updateFirstPlayer($pId)
  {
    self::notifyAll('updateFirstPlayer', '', [
      'pId' => $pId,
    ]);
  }

  public static function possibleWorkerSpaces($pId, $spaces)
  {
    self::notify($pId,'possibleWorkerSpaces', '', [
      'pId' => $pId,
      'spaces' => $spaces,
    ]);
  }
  
  public static function giveMoney($player,$money){
    self::notifyAll('giveMoney', clienttranslate('${player_name} receives ${n} Francs'), [
      'player' => $player,
      'n' => $money,
    ]);
  }
  public static function spendMoney($player,$money){
    self::notifyAll('spendMoney', clienttranslate('${player_name} spends ${n} Francs'), [
      'player' => $player,
      'n' => $money,
    ]);
  }
  public static function placeWorkersInSpace($player,$toLocation,$nbWorkers){
    self::notifyAll('placeWorkers', clienttranslate('${player_name} places ${n} workers in ${space}'), [
      'player' => $player,
      'space' => $toLocation,
      'n' => $nbWorkers,
    ]);
  }
  public static function startMining($player,$nbMoves){
    self::notifyAll('startMining', clienttranslate('${player_name} gets ${n} mining work steps to perform'), [
      'player' => $player,
      'n' => $nbMoves,
    ]);
  }
  
  public static function refillFactorySpace($newTile){
    self::notifyAll('refillFactorySpace', clienttranslate('The game draws a new tile'), [
      'tile' => $newTile,
      //Space info is in the tile location
    ]);
  }
  
  public static function refillOrderSpace($newCard){
    self::notifyAll('refillOrderSpace', clienttranslate('The game draws a new card'), [
      'card' => $newCard,
      //Space info is in the card location
    ]);
  }
  
  public static function giveCardTo($player,$card){
    self::notifyAll('giveCardTo', clienttranslate('${player_name} receives a new order card'), [
      'player' => $player,
      'card' => $card,
    ]);
  }
  public static function giveTileTo($player,$tile){
    self::notifyAll('giveTileTo', clienttranslate('${player_name} receives a new tunnel tile'), [
      'player' => $player,
      'tile' => $tile,
    ]);
  }
  public static function moveCoalTo($player,$tile,$coal){
    self::notifyAll('moveCoalTo', clienttranslate('${player_name} receives a new coal on the new tunnel tile'), [
      'player' => $player,
      'tile' => $tile,
      'coal' => $coal,
    ]);
  }

  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    Game::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  /*********************
   **** UPDATE ARGS ****
   *********************/

  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      unset($data['player']);
    }

    if (isset($data['player2'])) {
      $data['player_name2'] = $data['player2']->getName();
      $data['player_id2'] = $data['player2']->getId();
      unset($data['player2']);
    }
  }
}
