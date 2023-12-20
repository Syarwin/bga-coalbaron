<?php

namespace COAL\Managers;

use COAL\Helpers\Utils;
use COAL\Helpers\Collection;
use COAL\Core\Notifications;
use COAL\Core\Stats;

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
    return new \COAL\Models\TileCard($row, $data);
  }

  public static function getUiData()
  {
    //TODO JSA FILTER VISIBLE Locations ONLY
    return self::getInLocation("deck")
      ->map(function ($tile) {
        return $tile->getUiData();
      })
      ->toAssoc();
  }

  /* Creation of the tiles */
  public static function setupNewGame($players, $options)
  {
    $tiles = [];
    // Create the deck
    foreach (self::getTiles() as $type => $tile) {
      $tiles[] = [
        'type' => $type,
        'location' => 'deck',
        'nbr' => TILES_EACH_NB,
      ];
    }

    self::create($tiles);
  }

  public function getTiles()
  {
    $f = function ($t) {
      return [
        'color' => $t[0],
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
