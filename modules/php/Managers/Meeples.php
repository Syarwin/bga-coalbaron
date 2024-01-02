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
    switch ($row['type']) {
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

  /**
   * Return Workers and COALS
   */
  public static function getUiData()
  {
    return self::DB()
      ->get()
      ->map(function ($coal) {
        return $coal->getUiData();
      })
      ->toArray();
  }

  /**
   * Return COALS from parametered player
   */
  public static function getCoalsUiData($pId)
  {
    return self::getPlayerCoals($pId)
      ->map(function ($coal) {
        return $coal->getUiData();
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
      foreach (Tiles::getPlayersBaseTiles($pId) as $baseTileCard) {
        $meeples[] = [
          'type' => $baseTileCard->getCoalColor(),
          'location' => $baseTileCard->getLocation(),
          'player_id' => $pId,
          'nbr' => 1,
        ];
      }
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
   * Move all meeples of specified types to location 
   */
  public static function moveAllByType($types, $location)
  {
    $data = [];
    $data[static::$prefix . 'location'] = $location;
    $query = self::DB()->update($data);
    $query->whereIn(
      'type',
      is_array($types) ? $types : [$types]
    );
    return $query->run();
  }
  /**
   * Return number of workers in factory for this player
   */
  // public static function getNbWorkersInFactory($player)
  // {
  //   $pId = is_int($player) ? $player : $player->getId();
  //   return self::getFilteredQuery($pId, SPACE_FACTORY . '%', WORKER)->count();
  // }
  /**
   * Return an array of player ids who have the majority in Factory spaces;
   *  EMPTY if no player is in factory
   */
  public static function getPlayerMajorityInFactory()
  {
    $factorySpace = SPACE_FACTORY . '%';
    $meeples = static::$table;
    $meeple_location = static::$prefix . 'location';
    //SELECT count(1), player_id FROM `meeples` 
    //WHERE `meeple_location` like 'factory%' group by `player_id`
    $sql = "SELECT player_id, count(1) 'nb'";
    $sql.= " FROM `$meeples`";
    $sql.= " WHERE `$meeple_location` like '$factorySpace'";
    $sql.= " group by `player_id` ";
    $countPerPlayers = new Collection(self::getCollectionFromDB($sql,true));
    
    $maxCount = 0;
    $maxPlayers = array();
    
    foreach($countPerPlayers as $pId => $countPerPlayer){
      if($maxCount < $countPerPlayer){
        $maxCount = $countPerPlayer;
        $maxPlayers = array($pId);
      }
      else if($maxCount == $countPerPlayer){
        $maxPlayers[] = $pId;
      }
    }
    
    return $maxPlayers;
  }
  /**
   * @return int number of ALL workers in that $location, or ALL workers if location not given
   */
  public static function countWorkers($location = null)
  {
    $query = self::getSelectQuery()->where('type','=',WORKER);
    if ($location != null) {
        $query = $query->where(
            static::$prefix . 'location',
            strpos($location, '%') === false ? '=' : 'LIKE',
            $location
        );
    }
    return $query->count();
  }
  /**
   * Return number of available workers for all players (of for parametered player when set) , by reading the DB
   */
  public static function getNbAvailableWorkers($player = null)
  {
    $pId = is_null($player) ? null : (is_int($player) ? $player : $player->getId());
    return self::getFilteredQuery($pId, SPACE_RESERVE, WORKER)->count();
  }

  /**
   * Return 0 to N available workers for this player, by reading the DB
   */
  public static function getFirstAvailableWorkers($pId, $number)
  {
    return self::getFilteredQuery($pId, SPACE_RESERVE, WORKER)
      ->limit($number)
      ->get();
  }
  /**
   * Return all available workers for this player, by filtering the array in parameter
   */
  public static function findAvailableWorkersInCollection($allWorkers, $pId)
  {
    $availableWorkers = $allWorkers->filter(function ($meeple) use ($pId) {
      return $meeple->getType() == WORKER && $meeple->getLocation() == SPACE_RESERVE && $meeple->getPId() == $pId;
    });
    return $availableWorkers;
  }

  public static function placeWorkersInSpace($player, $toLocation)
  {
    $pId = $player->getId();
    $nbNeededWorkers = Meeples::countInLocation($toLocation) + 1;
    //MOVE PREVIOUS WORKERS to the CANTEEN before placing new workers !
    self::moveAllInLocation($toLocation, SPACE_CANTEEN);
    //pickForLocation is good for location but doesnt filter pid...
    //self::pickForLocation($nbNeededWorkers,SPACE_RESERVE,$toLocation);
    $workersToMove = self::getFirstAvailableWorkers($pId, $nbNeededWorkers);
    foreach ($workersToMove as $worker) {
      $workersIds[] = $worker->getId();
    }
    $nbAv = count($workersIds);
    if ($nbAv < $nbNeededWorkers) {
      //Should not happen
      throw new \BgaVisibleSystemException("Not enough workers ($nbAv < $nbNeededWorkers)");
    }
    Meeples::move($workersIds, $toLocation);

    Notifications::placeWorkersInSpace($player, $toLocation, $workersIds);
  }

  public static function getFirstAvailableCoals($color, $number)
  {
    return self::getFilteredQuery(null, SPACE_RESERVE, $color)
      ->limit($number)
      ->get();
  }
  /**
   * @return int number of ALL COALS owned by that player and in that $location,
   *   or ALL COALS owned by that player if location not given
   */
  public static function countPlayerCoals($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location, '%_coal')->count();
  }
  /**
   * Return all coals currently owned by this player
   */
  public static function getPlayerCoals($pId)
  {
    return self::getFilteredQuery($pId, null, '%_coal')->get();
  }
  /**
   * Return all coals currently in cage of this player
   */
  public static function getPlayerCageCoals($pId)
  {
    return self::getFilteredQuery($pId, SPACE_PIT_CAGE, '%_coal')->get();
  }
  /**
   * Return all coals currently on a card of this player
   */
  public static function getPlayerCardCoals($pId, $cardId)
  {
    return self::getFilteredQuery($pId, COAL_LOCATION_CARD . $cardId . '%', '%_coal')->get();
  }
  /**
   * Return all coals currently on a tile of this player
   */
  public static function getPlayerTileCoals($pId, $tileId)
  {
    return self::getFilteredQuery($pId, COAL_LOCATION_TILE . $tileId . '%', '%_coal')->get();
  }
  /**
   * @return number of coal cubes on specified location AND player
   */
  public static function countPlayerCoalsOnLocation($pId, $location)
  {
    return self::getFilteredQuery($pId, $location, '%_coal')->count();
  }
  /**
   * ADD 1 COAL in each minecart on the tile
   */
  public static function placeCoalsOnTile($player, $tile)
  {
    $nb = $tile->getNumber();
    $coals = self::getFirstAvailableCoals($tile->getColor(), $nb);
    foreach ($coals as $coal) {
      $coal->moveToTile($player, $tile);
    }

    $missingCoals = $nb - count($coals);
    if ($missingCoals > 0) {
      //TODO JSA SPECIFIC STATE to choose a coal
      /*
      If, when a player acquires a tunnel tile, the supply contains fewer coal cubes of the color he needs to fill the minecarts on that tile, the player may place 1 coal cube of
      any color onto each minecart on that tile which he cannot fill properly. This does not affect any costs paid for the tile.
      */
      throw new \BgaVisibleSystemException('Not supported feature : choose coal');
    }
  }
}
