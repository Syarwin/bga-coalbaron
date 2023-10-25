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
        'nbr' => $nWorkers[count($players)],
      ];
    }

    self::create($meeples);
  }
}
