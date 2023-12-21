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
    $data['type'] = $row['type'];
    switch($row['type']){
      case WORKER:
        return new \COAL\Models\Worker($row, $data);
      case YELLOW_COAL: 
      case BROWN_COAL: 
      case BLACK_COAL: 
      case GREY_COAL:
        return new \COAL\Models\CoalCube($row, $data);
    }
    return new \COAL\Models\Meeple($row, $data);
  }

  public static function getUiData()
  {
    return self::DB()->get()
      ->map(function ($card) {
        return $card->getUiData();
      })
      ->toAssoc();
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
      //Fill each player's starting minecarts with 1 coal cube :
      $meeples[] = [
        'type' => YELLOW_COAL,
        'location' => SPACE_PIT_TILE."_1_1",
        'player_id' => $pId,
        'nbr' => 1,
      ];
      $meeples[] = [
        'type' => BROWN_COAL,
        'location' => SPACE_PIT_TILE."_2_-1",
        'player_id' => $pId,
        'nbr' => 1,
      ];
      $meeples[] = [
        'type' => GREY_COAL,
        'location' => SPACE_PIT_TILE."_3_-1",
        'player_id' => $pId,
        'nbr' => 1,
      ];
      $meeples[] = [
        'type' => BLACK_COAL,
        'location' => SPACE_PIT_TILE."_4_1",
        'player_id' => $pId,
        'nbr' => 1,
      ];
    }
    //Place all other Coal Cubes in reserve : 16 exist in each color 
    $nbCubesOfEach = 16 - count($players);
    $meeples[] = [
      'type' => YELLOW_COAL,
      'location' => SPACE_RESERVE,
      'player_id' => null,
      'nbr' => $nbCubesOfEach,
    ];
    $meeples[] = [
      'type' => BROWN_COAL,
      'location' => SPACE_RESERVE,
      'player_id' => null,
      'nbr' => $nbCubesOfEach,
    ];
    $meeples[] = [
      'type' => GREY_COAL,
      'location' => SPACE_RESERVE,
      'player_id' => null,
      'nbr' => $nbCubesOfEach,
    ];
    $meeples[] = [
      'type' => BLACK_COAL,
      'location' => SPACE_RESERVE,
      'player_id' => null,
      'nbr' => $nbCubesOfEach,
    ];
    
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
      return $meeple->getType() == WORKER
          && $meeple->getLocation() == SPACE_RESERVE
          && $meeple->getPId() == $pId ;
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
      $workersIds[] = $worker->getId();
    }
    $nbAv = count($workersIds);
    if($nbAv < $nbNeededWorkers ) //Should not happen
      throw new \BgaVisibleSystemException("Not enough workers ($nbAv < $nbNeededWorkers)");
    Meeples::move($workersIds,$toLocation );

    Notifications::placeWorkersInSpace($player,$toLocation,$nbNeededWorkers);
  }
}
