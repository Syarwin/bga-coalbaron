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
        'location' => SPACE_RESERVE,
        'player_id' => $pId,
        'nbr' => $nWorkers[count($players)],
      ];
    }

    self::create($meeples);
  }
  
  /**
   * Return number of available workers for this player, by reading the DB
   */
  public static function getNbAvailableWorkers($player)
  {
    $pId = $player->getId();
    return self::getFilteredQuery($pId, SPACE_RESERVE, WORKER)->count();
  }
  
  /**
   * Return 0 to N available workers for this player, by reading the DB
   */
  public static function getFirstAvailableWorkers($pId,$number)
  {
    return self::getFilteredQuery($pId, SPACE_RESERVE, WORKER)->limit($number)->get();
  }
  /**
   * Return all available workers for this player, by filtering the array in parameter
   */
  public static function findAvailableWorkersInCollection($allWorkers,$pId)
  { 
    $availableWorkers = $allWorkers->filter(function ($meeple) use ($pId){
      return $meeple['type'] == WORKER
          && $meeple['meeple_location'] == SPACE_RESERVE
          && $meeple['player_id'] == $pId ;
    });
    return $availableWorkers;
  }
  
  public static function placeWorkersInSpace($player,$toLocation)
  {
    $pId = $player->getId();
    $nbNeededWorkers = Meeples::countInLocation($toLocation) +1;
    //MOVE PREVIOUS WORKERS to the CANTEEN before placing new workers !
    self::moveAllInLocation($toLocation ,SPACE_CANTEEN  );
    //pickForLocation is good for location but doesnt filter pid...
    //self::pickForLocation($nbNeededWorkers,SPACE_RESERVE,$toLocation);
    $workersToMove = self::getFirstAvailableWorkers($pId,$nbNeededWorkers);
    foreach ($workersToMove as $worker) {
      $workersIds[] = $worker['meeple_id'];
    }
    $nbAv = count($workersIds);
    if($nbAv < $nbNeededWorkers ) //Should not happen
      throw new \BgaVisibleSystemException("Not enough workers ($nbAv < $nbNeededWorkers)");
    Meeples::move($workersIds,$toLocation );

    Notifications::placeWorkersInSpace($player,$toLocation,$nbNeededWorkers);
  }
}
