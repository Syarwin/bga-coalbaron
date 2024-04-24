<?php

namespace COAL\Managers;

use COAL\Core\Notifications;
use COAL\Helpers\Collection;
use COAL\Models\BaseTileCard;
use COAL\Models\TileCard;

/* Class to manage all the tile cards for CoalBaron */

class Tiles extends \COAL\Helpers\Pieces
{
  protected static $table = 'tiles';
  protected static $prefix = 'tile_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['x', 'y', 'type', 'player_id'];

  protected static function cast($row)
  {
    $data = self::getTiles()[$row['type']];
    $data['type'] = $row['type'];
    return new TileCard($row, $data);
  }

  public static function getUiData()
  {
    //FILTER VISIBLE Locations ONLY
    return self::getSelectWhere(null, null, TILE_STATE_VISIBLE)
      ->whereNotIn(static::$prefix . 'location', [TILE_LOCATION_DECK])
      ->get()
      ->map(function ($tile) {
        return $tile->getUiData();
      })
      ->toArray();
  }

  /**
   * @return int number of cards in deck
   */
  public static function getDeckSize()
  {
    return self::countInLocation(TILE_LOCATION_DECK);
  }
  /**
   * For one tile, return the list of all coal spots with either the coal currently in the spot, either "EMPTY_SPOT"
   */
  public static function getTileCoalsStatus($tile)
  {
    $datas = [];
    for ($coalIndex = 0; $coalIndex < $tile->getNumber(); $coalIndex++) {
      $datas[$coalIndex] = [$tile->getColor() => COAL_EMPTY_SPOT];
    }
    $pId = $tile->getPId();
    if (!isset($pId)) {
      return $datas;
    }
    $filledCoals = Meeples::getPlayerTileCoals($pId, $tile->getId());
    $coalIndex = 0;
    foreach ($filledCoals as $filledCoal) {
      //loop CoalCube list
      $datas[$coalIndex] = [$tile->getColor() => $filledCoal];
      $coalIndex++;
    }
    return $datas;
  }

  /* Creation of the tiles */
  public static function setupNewGame($players, $options)
  {
    $tiles = [];
    // Create the deck
    foreach (self::getTiles() as $type => $tile) {
      $tiles[] = [
        'type' => $type,
        'location' => TILE_LOCATION_DECK,
        'nbr' => TILES_EACH_NB,
      ];
    }

    self::create($tiles);
    self::shuffle(TILE_LOCATION_DECK);

    self::drawTilesToFactory();
  }

  /**
   * Draw N new tiles and place them each unlocked factory space
   */
  public static function drawTilesToFactory()
  {
    self::refillEmptyFactorySpaces(false);
  }
  
  /**
   * Draw N new tiles and place them each unlocked EMPTY factory space
   * @param bool $sendNotif (false = silent by default)
   */
  public static function refillEmptyFactorySpaces($sendNotif = false)
  {
    //DRAW 6 or 8 tiles:
    $spaces = self::getUnlockedSpacesInFactory();
    foreach ($spaces as $space) {
      if ($space == SPACE_FACTORY_DRAW) {
        continue;
      }
      if(self::countInLocation($space) > 0) continue;
      $newTile = self::refillFactorySpace($space);
      if (isset($newTile) && $sendNotif) {
          Notifications::refillFactorySpace($newTile);
      }
    }
  }

  /**
   * Draw a new tile and place it in the factory $space
   */
  public static function refillFactorySpace($space)
  {
    $newTile = self::pickOneForLocation(TILE_LOCATION_DECK, $space, TILE_STATE_VISIBLE, false);
    return $newTile;
  }

  /**
   * Move all these ordered cards to the TOP of the deck after checking they are at $fromLocation
   * @param array $cardsIdArray
   * @param string $fromLocation
   * @param string $toLocation
   */
  public static function moveAllToTop($cardsIdArray, $fromLocation, $toLocation)
  {
    $cards = self::getMany($cardsIdArray);
    foreach ($cards as $card) {
      $cardId = $card->getId();
      if ($card->getLocation() != $fromLocation) {
        throw new \BgaVisibleSystemException("Tile $cardId is not at the right place ($fromLocation)");
      }
      self::insertOnTop($cardId, $toLocation);
    }
  }

  /**
   * Move all these ordered cards to the BOTTOM of the deck after checking they are at $fromLocation
   * @param array $cardsIdArray
   * @param string $fromLocation
   * @param string $toLocation
   */
  public static function moveAllToBottom($cardsIdArray, $fromLocation, $toLocation)
  {
    $cards = self::getMany($cardsIdArray);
    foreach ($cards as $card) {
      $cardId = $card->getId();
      if ($card->getLocation() != $fromLocation) {
        throw new \BgaVisibleSystemException("Tile $cardId is not at the right place ($fromLocation)");
      }
      self::insertAtBottom($cardId, $toLocation);
    }
  }
  /**
   * Read the tile in a specified factory space
   */
  public static function getTileInFactory($space)
  {
    $tile = self::getTopOf($space);
    return $tile;
  }

  /**
   * return next available column to place a tile on player board : according to color (row) and light (right/left)
   */
  public static function getPlayerNextColumnForTile($pId, $tile)
  {
    $row = $tile->getRow();

    //NB : for now, the default PIT TILES (SPACE_PIT_TILE) are not counted, because they don't use TILE model
    if ($tile->isLight()) {
      //LIGHT SIDE = LEFT SIDE = NEGATIVE X
      $xmin =
        self::DB()
          ->wherePlayer($pId)
          ->whereNotNull('x')
          ->where('y', $row)
          ->func('MIN', 'x') ?? 0;
      //negative X must start at -1 not 0 !
      $xmin = min($xmin, 0);
      return $xmin - 1;
    } else {
      //DARK SIDE = RIGHT SIDE = POSITIVE X
      $xmax =
        self::DB()
          ->wherePlayer($pId)
          ->whereNotNull('x')
          ->where('y', $row)
          ->func('MAX', 'x') ?? 0;
      //positive X must start at 1 not 0 !
      $xmax = max($xmax, 0);
      return $xmax + 1;
    }
  }

  /**
   * return the list of tiles owned by players
   */
  public static function getPlayersTiles()
  {
    return self::DB()
      ->whereNotNull('player_id')
      ->get();
  }
  /**
   * @param bool $light
   * @return int number of ALL (light OR dark) TILES owned by that player
   */
  public static function countPlayerTiles($pId, $light)
  {
    if ($light) {
      $types = self::getLightTypes();
    } else {
      $types = self::getDarkTypes();
    }
    return self::getFilteredQuery($pId, null, $types)->count();
  }

  /**
   * return the list of basic tiles owned by players (on default pit)
   */
  public static function getPlayersBaseTiles($pId)
  {
    $tiles = [];
    $tiles[] = new BaseTileCard($pId, MINECART_YELLOW, 1, 1);
    $tiles[] = new BaseTileCard($pId, MINECART_BROWN, 2, -1);
    $tiles[] = new BaseTileCard($pId, MINECART_GREY, 3, -1);
    $tiles[] = new BaseTileCard($pId, MINECART_BLACK, 4, 1);
    return new Collection($tiles);
  }
  /**
   * Return all unlocked spaces in factory
   */
  public static function getUnlockedSpacesInFactory()
  {
    $nbPlayers = Players::count();
    $spaces = [
      SPACE_FACTORY_1,
      SPACE_FACTORY_2,
      SPACE_FACTORY_3,
      SPACE_FACTORY_5,
      SPACE_FACTORY_6,
      SPACE_FACTORY_7,
      SPACE_FACTORY_DRAW,
    ];

    if ($nbPlayers >= 3) {
      $spaces[] = SPACE_FACTORY_4;
    }
    if ($nbPlayers >= 4) {
      $spaces[] = SPACE_FACTORY_8;
    }
    return $spaces;
  }

  /**
   * @return array list of all tiles types corresponding to a LIGHT tile
   */
  public static function getLightTypes()
  {
    return self::getTypes(true);
  }
  /**
   * @return array list of all tiles types corresponding to a DARK tile
   */
  public static function getDarkTypes()
  {
    return self::getTypes(false);
  }
  /**
   * @return array list of all tiles types corresponding to a LIGHT or DARK tile
   */
  public static function getTypes($isExpectedLight)
  {
    $tiles = self::getTiles();
    $lightTypes = [];
    foreach ($tiles as $type => $tile) {
      if ($tile['light'] == $isExpectedLight) {
        $lightTypes[] = $type;
      }
    }
    return $lightTypes;
  }

  public static function getTiles()
  {
    $f = function ($t) {
      return [
        'color' => $t[0],
        //Number of minecarts on the tile:
        'number' => $t[1],
        'light' => $t[2],
      ];
    };

    //48 tiles : 16 different tiles * 3 of each
    return [
      1 => $f([YELLOW_COAL, 1, false]),
      2 => $f([YELLOW_COAL, 2, false]),
      3 => $f([BROWN_COAL, 1, false]),
      4 => $f([BROWN_COAL, 2, false]),
      5 => $f([GREY_COAL, 1, false]),
      6 => $f([GREY_COAL, 2, false]),
      7 => $f([BLACK_COAL, 1, false]),
      8 => $f([BLACK_COAL, 2, false]),

      9 => $f([YELLOW_COAL, 1, true]),
      10 => $f([YELLOW_COAL, 2, true]),
      11 => $f([BROWN_COAL, 1, true]),
      12 => $f([BROWN_COAL, 2, true]),
      13 => $f([GREY_COAL, 1, true]),
      14 => $f([GREY_COAL, 2, true]),
      15 => $f([BLACK_COAL, 1, true]),
      16 => $f([BLACK_COAL, 2, true]),
    ];
  }
}
