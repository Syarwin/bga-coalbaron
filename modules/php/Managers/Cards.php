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
        'points' => $t[1],
        'coals' => $t[2],
      ];
    };
    return [
      // 11 different barrow
      $f([TRANSPORT_BARROW,2, [YELLOW_COAL]]),
      $f([TRANSPORT_BARROW,3, [BROWN_COAL]]),
      $f([TRANSPORT_BARROW,3, [YELLOW_COAL,YELLOW_COAL]]),
      $f([TRANSPORT_BARROW,4, [GREY_COAL]]),
      $f([TRANSPORT_BARROW,5, [BLACK_COAL]]),
      $f([TRANSPORT_BARROW,5, [BROWN_COAL,BROWN_COAL]]),
      $f([TRANSPORT_BARROW,7, [BROWN_COAL,BROWN_COAL,BROWN_COAL]]),
      $f([TRANSPORT_BARROW,7, [GREY_COAL,GREY_COAL]]),
      $f([TRANSPORT_BARROW,9, [BLACK_COAL,BLACK_COAL]]),
      $f([TRANSPORT_BARROW,9,  [GREY_COAL,GREY_COAL,GREY_COAL]]),
      $f([TRANSPORT_BARROW,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
      // 11 different motorcar
      $f([TRANSPORT_MOTORCAR,2, [YELLOW_COAL]]),
      $f([TRANSPORT_MOTORCAR,3, [YELLOW_COAL,YELLOW_COAL]]),
      $f([TRANSPORT_MOTORCAR,3, [BROWN_COAL]]),
      $f([TRANSPORT_MOTORCAR,4, [YELLOW_COAL,YELLOW_COAL,YELLOW_COAL]]),
      $f([TRANSPORT_MOTORCAR,4, [GREY_COAL]]),
      $f([TRANSPORT_MOTORCAR,5, [BROWN_COAL,BROWN_COAL]]),
      $f([TRANSPORT_MOTORCAR,5, [BLACK_COAL]]),
      $f([TRANSPORT_MOTORCAR,7, [GREY_COAL,GREY_COAL]]),
      $f([TRANSPORT_MOTORCAR,9, [BLACK_COAL,BLACK_COAL]]),
      $f([TRANSPORT_MOTORCAR,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
      $f([TRANSPORT_MOTORCAR,12, [BLACK_COAL,BLACK_COAL,BLACK_COAL]]),
      // 11 different carriage
      $f([TRANSPORT_CARRIAGE,2, [YELLOW_COAL]]),
      $f([TRANSPORT_CARRIAGE,3, [YELLOW_COAL,YELLOW_COAL]]),
      $f([TRANSPORT_CARRIAGE,3, [BROWN_COAL]]),
      $f([TRANSPORT_CARRIAGE,4, [YELLOW_COAL,YELLOW_COAL,YELLOW_COAL]]),
      $f([TRANSPORT_CARRIAGE,4, [GREY_COAL]]),
      $f([TRANSPORT_CARRIAGE,5, [BLACK_COAL]]),
      $f([TRANSPORT_CARRIAGE,5, [BROWN_COAL,BROWN_COAL]]),
      $f([TRANSPORT_CARRIAGE,7, [GREY_COAL,GREY_COAL]]),
      $f([TRANSPORT_CARRIAGE,9, [BLACK_COAL,BLACK_COAL]]),
      $f([TRANSPORT_CARRIAGE,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
      $f([TRANSPORT_CARRIAGE,12, [BLACK_COAL,BLACK_COAL,BLACK_COAL]]),
      // 11 different engine
      $f([TRANSPORT_ENGINE,2,  [YELLOW_COAL]]),
      $f([TRANSPORT_ENGINE,3,  [BROWN_COAL]]),
      $f([TRANSPORT_ENGINE,3,  [YELLOW_COAL,YELLOW_COAL]]),
      $f([TRANSPORT_ENGINE,4,  [GREY_COAL]]),
      $f([TRANSPORT_ENGINE,5,  [BROWN_COAL,BROWN_COAL]]),
      $f([TRANSPORT_ENGINE,5,  [BLACK_COAL]]),
      $f([TRANSPORT_ENGINE,7,  [BROWN_COAL,BROWN_COAL,BROWN_COAL]]),
      $f([TRANSPORT_ENGINE,7,  [GREY_COAL,GREY_COAL]]),
      $f([TRANSPORT_ENGINE,9,  [GREY_COAL,GREY_COAL,GREY_COAL]]),
      $f([TRANSPORT_ENGINE,9,  [BLACK_COAL,BLACK_COAL]]),
      $f([TRANSPORT_ENGINE,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
    ];
  }
}
