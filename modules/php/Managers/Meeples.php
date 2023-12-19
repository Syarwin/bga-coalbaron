<?php

namespace COAL\Managers;

use COAL\Helpers\Utils;
use COAL\Helpers\Collection;
use COAL\Core\Notifications;
use COAL\Core\Stats;

/* Class to manage all the meeples for CoalBaron */

class Meeples extends \COAL\Helpers\Pieces
{
  protected static $table = 'meeples';
  protected static $prefix = 'meeple_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['type', 'player_id'];

  protected static function cast($row)
  {
    return $row;
  }

  public static function getUiData()
  {
    return self::DB()->get();
  }

  /* Creation of the tiles */
  public static function setupNewGame($players, $options)
  {
    $meeples = [];

    $nWorkers = [2 => 18, 3 => 15, 4 => 13];
    foreach ($players as $pId => $player) {
      $meeples[] = [
        'type' => WORKER,
        'location' => "reserve-$pId",
        'player_id' => $pId,
        'nbr' => $nWorkers[count($players)],
      ];
    }

    self::create($meeples);
  }
  
  public static function getNbAvailableWorkers($player)
  {
    $pId = $player->getId();
    return Meeples::countInLocation("reserve-$pId");
  }
  
  public static function placeWorkersInSpace($player,$toLocation)
  {
    $pId = $player->getId();
    $nbNeededWorkers = Meeples::countInLocation($toLocation) +1;
    $fromLocation = "reserve-$pId";
    //MOVE PREVIOUS WORKERS to the CANTEEN before placing new workers !
    self::moveAllInLocation($toLocation ,SPACE_CANTEEN  );
    self::pickForLocation($nbNeededWorkers,$fromLocation,$toLocation);

    Notifications::placeWorkersInSpace($player,$toLocation,$nbNeededWorkers);
  }
}
