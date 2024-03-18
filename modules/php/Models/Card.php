<?php

namespace COAL\Models;

use COAL\Core\Stats;
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
    //$data['coalsStatus'] = Cards::getCardCoalsStatus($this->getId());
    $data['isCompleted'] = $this->isCompleted();
    return $data;
  }

  public function moveToOutstanding($player)
  {
    $this->setLocation(CARD_LOCATION_OUTSTANDING);
    $this->setPId($player->getId());
  }

  public function moveToDelivered()
  {
    $this->removeCoalCubes();
    $this->setLocation(CARD_LOCATION_DELIVERED);
    //PID must be already known
  }

  public function removeCoalCubes()
  {
    $nbCoals = count($this->getCoals());
    Stats::inc("coalsDelivered", $this->getPId(), $nbCoals);
    Stats::inc("coalsLeft", $this->getPId(), -$nbCoals);
    Cards::removeCoalCubesOnCard($this);
  }

  /**
   * Return true if all coals spots are occupied
   */
  public function isCompleted()
  {
    $coalsStatus = Cards::getCardCoalsStatus($this->getId());
    foreach ($coalsStatus as $coalStatus) {
      foreach ($coalStatus as $color => $status) {
        if ($status == COAL_EMPTY_SPOT) return false;
      }
    }
    return true;
  }
}
