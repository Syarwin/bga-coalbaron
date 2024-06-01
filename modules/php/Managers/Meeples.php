<?php

namespace COAL\Managers;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Helpers\Utils;
use COAL\Helpers\Collection;
use COAL\Core\Notifications;
use COAL\Core\Stats;
use COAL\Exceptions\MissingCoalException;
use COAL\Models\TileCard;

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
        Stats::inc("coalsLeft", $pId, 1);
        Stats::inc($baseTileCard->getCoalColor() . "Received", $pId, 1);
      }
    }
    //Place all other Coal Cubes in reserve : 16 exist in each color
    $nbCubesOfEach = 32; //16 - count($players);
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
    $sql .= " FROM `$meeples`";
    $sql .= " WHERE `$meeple_location` like '$factorySpace'";
    $sql .= " group by `player_id` ";
    $countPerPlayers = new Collection(Game::get()->getCollectionFromDB($sql, true));

    $maxCount = 0;
    $maxPlayers = array();

    foreach ($countPerPlayers as $pId => $countPerPlayer) {
      if ($maxCount < $countPerPlayer) {
        $maxCount = $countPerPlayer;
        $maxPlayers = array($pId);
      } else if ($maxCount == $countPerPlayer) {
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
    $query = self::getSelectQuery()->where('type', '=', WORKER);
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

  /**
   * Move enough workers from $player's reserve to $toLocation
   * @param Player $player
   * @param string $toLocation
   * @param int $fixedNbNeededWorkers (optional) A fixed number of workers in the same place whatever the workers already in
   */
  public static function placeWorkersInSpace($player, $toLocation, $fixedNbNeededWorkers = null)
  {
    $pId = $player->getId();
    if (isset($fixedNbNeededWorkers)) {
      $nbNeededWorkers = $fixedNbNeededWorkers;
    } else {
      $nbWorkersAtWork = Meeples::countInLocation($toLocation);
      $nbNeededWorkers = $nbWorkersAtWork + 1;
      //MOVE PREVIOUS WORKERS to the CANTEEN before placing new workers !
      self::moveAllInLocation($toLocation, SPACE_CANTEEN);
      if ($nbWorkersAtWork > 0) {
        Notifications::moveToCanteen($toLocation, $nbWorkersAtWork);
      }
    }
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
   * @return int number of ALL COALS in reserve of that color
   */
  public static function countAvailableCoals($color)
  {
    return self::getFilteredQuery(null, SPACE_RESERVE, $color)->count();
  }
  /**
   * @return array ARRAY of number of ALL COALS in reserve of EACH color
   */
  public static function countAvailableCoalsColorArray()
  {
    return array(
      YELLOW_COAL => self::countAvailableCoals(YELLOW_COAL),
      BROWN_COAL => self::countAvailableCoals(BROWN_COAL),
      GREY_COAL => self::countAvailableCoals(GREY_COAL),
      BLACK_COAL => self::countAvailableCoals(BLACK_COAL),
    );
  }
  /**
   * @return int number of ALL COALS owned by that player and in that $location,
   *   or ALL COALS owned by that player if location not given
   */
  public static function countPlayerCoals($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location, '%\_coal')->count();
  }
  /**
   * Return all coals currently owned by this player
   */
  public static function getPlayerCoals($pId)
  {
    return self::getFilteredQuery($pId, null, '%\_coal')->get();
  }
  /**
   * Return all coals currently in cage of this player
   */
  public static function getPlayerCageCoals($pId)
  {
    return self::getFilteredQuery($pId, SPACE_PIT_CAGE, '%\_coal')->get();
  }
  /**
   * Return all coals currently on a card of this player
   */
  public static function getPlayerCardCoals($pId, $cardId)
  {
    return self::getFilteredQuery($pId, COAL_LOCATION_CARD . $cardId . '\_%', '%\_coal')->get();
  }
  /**
   * Return all coals currently on a tile of this player
   */
  public static function getPlayerTileCoals($pId, $tileId)
  {
    return self::getFilteredQuery($pId, COAL_LOCATION_TILE . $tileId, '%\_coal')->get();
  }
  /**
   * @return number of coal cubes on specified location AND player
   */
  public static function countPlayerCoalsOnLocation($pId, $location)
  {
    return self::getFilteredQuery($pId, $location, '%\_coal')->count();
  }
  /**
   * ADD 1 COAL in each minecart on the tile
   * @param Player $player
   * @param TileCard $tile
   */
  public static function placeCoalsOnTile($player, $tile)
  {
    $tileId = $tile->getId();
    $nb = $tile->getNumber();
    $color = $tile->getColor();
    Game::get()->trace("placeCoalsOnTile( $tileId, $color,$nb)");
    $coals = Meeples::placeAnyCoalOnTile($player, $tile, $color, $nb);

    $missingCoals = $nb - count($coals);
    if ($missingCoals > 0) {
      /*
      GAME RULE (last additional note): 
      If, when a player acquires a tunnel tile, the supply contains fewer coal cubes of the color he needs to fill the minecarts on that tile, the player may place 1 coal cube of
      ANY COLOR onto each minecart on that tile which he cannot fill properly. This does not affect any costs paid for the tile.
      */
      $coalsInReserve = Meeples::countAvailableCoalsColorArray();

      // SHOULD NOT HAPPEN anymore with increased coals limits in reserve...
      //TODO SPECIAL CASE : only 1 color in reserve : auto choose with confirmation ?
      //TODO SPECIAL CASE : $missingCoals coals in reserve : auto choose with confirmation ?
      //TODO SPECIAL CASE : 0 coal in reserve -> confirm user empty choice ?

      Globals::setNbCoalsToChoose($missingCoals);
      Globals::setTileCoalsToChoose($tileId);

      throw new MissingCoalException("$missingCoals missing coal for tile $tileId");
    }
  }
  /**
   * ADD N COALS of a specific color on the tile
   * @param Player $player
   * @param TileCard $tile
   * @param string $color type of the coal
   * @param int $number number of coals of this colors to add
   * @return Collection coals placed on that tile
   */
  public static function placeAnyCoalOnTile($player, $tile, $color, $number)
  {
    $tileId = $tile->getId();
    Game::get()->trace("placeAnyCoalOnTile( $tileId, $color, $number)");
    $coals = self::getFirstAvailableCoals($color, $number);
    foreach ($coals as $coal) {
      $coal->moveToTile($player, $tile);
      Stats::inc($coal->getType() . "Received", $player);
    }
    Stats::inc("coalsReceived", $player, count($coals));
    Stats::inc("coalsLeft", $player, count($coals));
    return $coals;
  }
}
