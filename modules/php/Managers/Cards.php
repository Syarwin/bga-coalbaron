<?php

namespace COAL\Managers;

use COAL\Core\Globals;
use COAL\Helpers\Utils;
use COAL\Helpers\Collection;
use COAL\Core\Notifications;
use COAL\Core\Stats;

/* Class to manage all the god cards for CoalBaron */

class Cards extends \COAL\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $autoIncrement = true;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['player_id', 'type'];

  protected static function cast($row)
  {
    return $row;
    // $data = self::getCards()[$row['god_id']];
    // return new \COAL\Models\GodCard($row, $data);
  }

  public static function getUiData()
  {
    return [];
  }

  /* Creation of the cards */
  public static function setupNewGame($players, $options)
  {
    $cards = [];

    foreach (self::getCards() as $type => $card) {
      $cards[] = [
        'location' => 'deck',
        'type' => $type,
      ];
    }

    self::create($cards);
  }

  public function getCards()
  {
    $f = function ($t) {
      return [
        'transport' => $t[0],
        'color' => $t[1],
        'number' => $t[2],
        'points' => $t[3],
      ];
    };

    return [$f(['train', 'yellow', 2, 5])];
  }
}
