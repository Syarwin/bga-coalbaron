<?php

namespace COAL\Models;

use COAL\Managers\Cards;

/*
 * Card: all utility functions concerning a card
 */

class Card extends \COAL\Helpers\DB_Model
{
  protected $table = 'cards';
  protected $primary = 'card_id';
  protected $attributes = [
    'id' => ['card_id', 'int'],
    'state' => ['card_state', 'int'],
    'location' => 'card_location',
    'pId' => ['player_id', 'int'],
  ];
  
  protected $staticAttributes = [
    ['type', 'int'],
    'transport',
    ['points', 'int'],
    'coals', //array of strings
  ];

  public function __construct($row, $datas)
  {
    parent::__construct($row);
    foreach ($datas as $attribute => $value) {
      $this->$attribute = $value;
    }
  }

  public function getUiData()
  {
    $data = parent::getUiData();
    $data['coalsStatus'] = Cards::getCardCoalsStatus($this->getId());
    return $data;
  }

  public function moveToOutstanding($player)
  {
    $this->setLocation(CARD_LOCATION_OUTSTANDING);
    $this->setPId($player->getId());
  }
 
}
