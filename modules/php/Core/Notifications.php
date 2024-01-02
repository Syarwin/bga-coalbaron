<?php

namespace COAL\Core;

use COAL\Managers\Players;
use COAL\Helpers\Utils;
use COAL\Core\Globals;

class Notifications
{
  public static function newShift($shift)
  {
    self::notifyAll('newShift', clienttranslate('Game starts shift ${n}/${m}'), [
      'n' => $shift,
      'm' => SHIFT_MAX,
    ]);
  }
  public static function endShift($shift)
  {
    self::notifyAll('endShift', clienttranslate('Game ends shift ${n}/${m} because no more workers are available'), [
      'n' => $shift,
      'm' => SHIFT_MAX,
    ]);
  }
  
  public static function updateFirstPlayer($player)
  {
    self::notifyAll('updateFirstPlayer', clienttranslate('${player_name} is the first player during this shift'), [
      'player' => $player,
    ]);
  }
  public static function skipTurn($player)
  {
    self::notifyAll('skipTurn', clienttranslate('${player_name} turn is skipped because 0 workers left'), [
      'player' => $player,
    ]);
  }

  public static function possibleWorkerSpaces($pId, $spaces)
  {
    self::notify($pId, 'possibleWorkerSpaces', '', [
      'pId' => $pId,
      'spaces' => $spaces,
    ]);
  }

  public static function giveMoney($player, $money)
  {
    self::notifyAll('giveMoney', clienttranslate('${player_name} receives ${n} Francs'), [
      'player' => $player,
      'n' => $money,
    ]);
  }
  public static function spendMoney($player, $money)
  {
    self::notifyAll('spendMoney', clienttranslate('${player_name} spends ${n} Francs'), [
      'player' => $player,
      'n' => $money,
    ]);
  }
  public static function placeWorkersInSpace($player, $toLocation, $workerIds)
  {
    $spaceName = $toLocation;
    $prefix = explode('_', $spaceName);
    if ($prefix == 'factory') {
      $spaceName = clienttranslate('factory');
    }
    // TODO

    self::notifyAll('placeWorkers', clienttranslate('${player_name} places ${n} workers in ${space_name}'), [
      'player' => $player,
      'space' => $toLocation,
      'n' => count($workerIds),
      'workerIds' => $workerIds,
      'space_name' => $spaceName,
      'i18n' => ['space_name'],
    ]);
  }
  public static function startMining($player, $nbMoves)
  {
    self::notifyAll('startMining', clienttranslate('${player_name} gets ${n} mining work steps to perform'), [
      'player' => $player,
      'n' => $nbMoves,
    ]);
  }

  public static function refillFactorySpace($newTile)
  {
    self::notifyAll('refillFactorySpace', clienttranslate('The game draws a new tile'), [
      'tile' => $newTile,
      //Space info is in the tile location
    ]);
  }

  public static function startDraft()
  {
    self::notifyAll('startDraft', clienttranslate('Starting draft : each player will take 3 cards'), [
    ]);
  }
  public static function endDraft($lastCard)
  {
    self::notifyAll('endDraft', clienttranslate('Ending draft : the last card goes to the board'), [
      'card' => $lastCard,
      //Space info is in the card location
    ]);
  }
  public static function refillOrderSpace($newCard)
  {
    self::notifyAll('refillOrderSpace', clienttranslate('The game draws a new card'), [
      'card' => $newCard,
      //Space info is in the card location
    ]);
  }

  public static function giveCardTo($player, $card)
  {
    self::notifyAll('giveCardTo', clienttranslate('${player_name} receives a new order card'), [
      'player' => $player,
      'card' => $card,
    ]);
  }

  public static function cardDelivered($player, $card)
  {
    self::notifyAll('cardDelivered', clienttranslate('${player_name} delivers an order card and receives ${n} points'), [
      'player' => $player,
      'card' => $card,
      'n' => $card->getPoints(),
    ]);
  }
  public static function giveTileTo($player, $tile)
  {
    self::notifyAll('giveTileTo', clienttranslate('${player_name} receives a new tunnel tile'), [
      'player' => $player,
      'tile' => $tile,
    ]);
  }
  public static function moveCoalToTile($player, $tile, $coal)
  {
    self::notifyAll('moveCoalToTile', clienttranslate('${player_name} receives a new coal on the new tunnel tile'), [
      'player' => $player,
      'tile' => $tile,
      'coal' => $coal,
    ]);
  }
  public static function moveCoalToCard($player, $card, $spotIndex, $coal)
  {
    self::notifyAll('moveCoalToCard', clienttranslate('${player_name} moves a coal to an order card'), [
      'player' => $player,
      'card' => $card,
      'spotIndex' => $spotIndex,
      'coal' => $coal,
    ]);
  }
  public static function moveCoalToCage($player, $coal)
  {
    self::notifyAll('moveCoalToCage', clienttranslate('${player_name} moves a coal to the pit cage'), [
      'player' => $player,
      'coal' => $coal,
    ]);
  }
  public static function moveCoalToStorage($player, $coal)
  {
    self::notifyAll('moveCoalToStorage', clienttranslate('${player_name} moves a coal to the private storage'), [
      'player' => $player,
      'coal' => $coal,
    ]);
  }

  public static function movePitCage($player, $fromLevel, $toLevel)
  {
    self::notifyAll('movePitCage', clienttranslate('${player_name} moves the pit cage from ${a} to ${b}'), [
      'player' => $player,
      'a' => $fromLevel,
      'b' => $toLevel,
    ]);
  }
  public static function stopMining($player, $moves, $movesTotal)
  {
    self::notifyAll('movePitCage', clienttranslate('${player_name} stops with ${a}/${b} moves remaining'), [
      'player' => $player,
      'a' => $moves,
      'b' => $movesTotal,
    ]);
  }

  public static function noDeliveries()
  {
    self::notifyAll('noDeliveries', clienttranslate('No delivery during this shift, thus no one scores points'), [
    ]);
  }
  public static function noPlayerDeliveries($player)
  {
    self::notifyAll('noPlayerDeliveries', clienttranslate('${player_name} did no delivery during this shift, thus scores 0 points'), [
      'player' => $player,
    ]);
  }
  /**
   * Resend cards to UI to see cards during end shift, then the UI must hide them again
   */
  public static function endShiftDeliveries($deliveredOrders)
  {
    $cards = $deliveredOrders->map(function ($card) {
        return $card->getUiData();
      }) ->toAssoc();

    self::notifyAll('endShiftDeliveries', '', [
      'cards' => $cards,
    ]);
  }
  public static function endShiftMajority($player, $points, $type,$majorityIndex, $nbElements)
  {
    self::notifyAll('endShiftMajority', clienttranslate('${player_name} scores ${p} points for the ${i}th majority (with ${n} elements)'), [
      'player' => $player,
      'p' => $points,
      'i' => $majorityIndex,
      'n' => $nbElements,
      //TODO : see if useful for adding an icon :
      'type' => $type,
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
