<?php

namespace COAL\Managers;

use COAL\Core\Notifications;

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
    $data = self::getCards()[$row['type']];
    $data['type'] = $row['type'];
    return new \COAL\Models\Card($row, $data);
  }

  public static function getUiData()
  {
    return self::getInLocation(CARD_LOCATION_OUTSTANDING)
      ->map(function ($card) {
        return $card->getUiData();
      })
      ->toAssoc();
  }

  public static function giveCardTo($player,$card){
    $card->moveToOutstanding($player);
    Notifications::giveCardTo($player,$card);
  }

  /* Creation of the cards */
  public static function setupNewGame($players, $options)
  {
    $cards = [];

    foreach (self::getCards() as $type => $card) {
      $cards[] = [
        'location' => CARD_LOCATION_DECK,
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
      1=>  $f([TRANSPORT_BARROW,2, [YELLOW_COAL]]),
      2=>  $f([TRANSPORT_BARROW,3, [BROWN_COAL]]),
      3=>  $f([TRANSPORT_BARROW,3, [YELLOW_COAL,YELLOW_COAL]]),
      4=>  $f([TRANSPORT_BARROW,4, [GREY_COAL]]),
      5=>  $f([TRANSPORT_BARROW,5, [BLACK_COAL]]),
      6=>  $f([TRANSPORT_BARROW,5, [BROWN_COAL,BROWN_COAL]]),
      7=>  $f([TRANSPORT_BARROW,7, [BROWN_COAL,BROWN_COAL,BROWN_COAL]]),
      8=>  $f([TRANSPORT_BARROW,7, [GREY_COAL,GREY_COAL]]),
      9=>  $f([TRANSPORT_BARROW,9, [BLACK_COAL,BLACK_COAL]]),
      10=> $f([TRANSPORT_BARROW,9,  [GREY_COAL,GREY_COAL,GREY_COAL]]),
      11=> $f([TRANSPORT_BARROW,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
      // 11 different motorcar
      12=> $f([TRANSPORT_MOTORCAR,2, [YELLOW_COAL]]),
      13=> $f([TRANSPORT_MOTORCAR,3, [YELLOW_COAL,YELLOW_COAL]]),
      14=> $f([TRANSPORT_MOTORCAR,3, [BROWN_COAL]]),
      15=> $f([TRANSPORT_MOTORCAR,4, [YELLOW_COAL,YELLOW_COAL,YELLOW_COAL]]),
      16=> $f([TRANSPORT_MOTORCAR,4, [GREY_COAL]]),
      17=> $f([TRANSPORT_MOTORCAR,5, [BROWN_COAL,BROWN_COAL]]),
      18=> $f([TRANSPORT_MOTORCAR,5, [BLACK_COAL]]),
      19=> $f([TRANSPORT_MOTORCAR,7, [GREY_COAL,GREY_COAL]]),
      20=> $f([TRANSPORT_MOTORCAR,9, [BLACK_COAL,BLACK_COAL]]),
      21=> $f([TRANSPORT_MOTORCAR,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
      22=> $f([TRANSPORT_MOTORCAR,12, [BLACK_COAL,BLACK_COAL,BLACK_COAL]]),
      // 11 different carriage
      23=> $f([TRANSPORT_CARRIAGE,2, [YELLOW_COAL]]),
      24=> $f([TRANSPORT_CARRIAGE,3, [YELLOW_COAL,YELLOW_COAL]]),
      25=> $f([TRANSPORT_CARRIAGE,3, [BROWN_COAL]]),
      26=> $f([TRANSPORT_CARRIAGE,4, [YELLOW_COAL,YELLOW_COAL,YELLOW_COAL]]),
      27=> $f([TRANSPORT_CARRIAGE,4, [GREY_COAL]]),
      28=> $f([TRANSPORT_CARRIAGE,5, [BLACK_COAL]]),
      29=> $f([TRANSPORT_CARRIAGE,5, [BROWN_COAL,BROWN_COAL]]),
      30=> $f([TRANSPORT_CARRIAGE,7, [GREY_COAL,GREY_COAL]]),
      31=> $f([TRANSPORT_CARRIAGE,9, [BLACK_COAL,BLACK_COAL]]),
      32=> $f([TRANSPORT_CARRIAGE,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
      33=> $f([TRANSPORT_CARRIAGE,12, [BLACK_COAL,BLACK_COAL,BLACK_COAL]]),
      // 11 different engine
      34=> $f([TRANSPORT_ENGINE,2,  [YELLOW_COAL]]),
      35=> $f([TRANSPORT_ENGINE,3,  [BROWN_COAL]]),
      36=> $f([TRANSPORT_ENGINE,3,  [YELLOW_COAL,YELLOW_COAL]]),
      37=> $f([TRANSPORT_ENGINE,4,  [GREY_COAL]]),
      38=> $f([TRANSPORT_ENGINE,5,  [BROWN_COAL,BROWN_COAL]]),
      39=> $f([TRANSPORT_ENGINE,5,  [BLACK_COAL]]),
      40=> $f([TRANSPORT_ENGINE,7,  [BROWN_COAL,BROWN_COAL,BROWN_COAL]]),
      41=> $f([TRANSPORT_ENGINE,7,  [GREY_COAL,GREY_COAL]]),
      42=> $f([TRANSPORT_ENGINE,9,  [GREY_COAL,GREY_COAL,GREY_COAL]]),
      43=> $f([TRANSPORT_ENGINE,9,  [BLACK_COAL,BLACK_COAL]]),
      44=> $f([TRANSPORT_ENGINE,10, [YELLOW_COAL,BROWN_COAL, GREY_COAL,BLACK_COAL]]),
    ];
  }
}
