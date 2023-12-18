<?php
namespace COAL;
use COAL\Core\Globals;
use COAL\Core\Game;
use COAL\Core\Notifications;
use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Helpers\Utils;

trait DebugTrait
{
  function test()
  {
    Notifications::message("START DEBUG TEST 1");

    $pId = Players::getCurrentId();
    $nbPlayers = 2;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers);
    Notifications::notifyPossibleWorkerSpaces($pId,$spaces);

    Notifications::message("END DEBUG TEST 1");
    
    Notifications::message("START DEBUG TEST 2");

    $nbPlayers = 3;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers);
    Notifications::notifyPossibleWorkerSpaces($pId,$spaces);

    Notifications::message("END DEBUG TEST 2");
    
    Notifications::message("START DEBUG TEST 3");

    $nbPlayers = 4;
    $spaces = self::getPossibleSpaces($pId, $nbPlayers);
    Notifications::notifyPossibleWorkerSpaces($pId,$spaces);

    Notifications::message("END DEBUG TEST 3");
  }
}
