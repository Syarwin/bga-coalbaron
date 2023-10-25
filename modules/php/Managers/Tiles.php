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
    return $row;
    // $data = self::getTiles()[$row['data_id']];
    // return new \COAL\Models\TileCard($row, $data);
  }

  public static function getUiData()
  {
    return [];
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

    return [$f(['yellow', 2, true])];
  }
}
