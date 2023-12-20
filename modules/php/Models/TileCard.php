<?php

namespace COAL\Models;

use COAL\Core\Notifications;

/*
 * TileCard: all utility functions concerning a tile
 */

class TileCard extends \COAL\Helpers\DB_Model
{
  protected $table = 'tiles';
  protected $primary = 'tile_id';
  protected $attributes = [
    'id' => ['tile_id', 'int'],
    'state' => ['tile_state', 'int'],
    'location' => 'tile_location',
    'pId' => ['player_id', 'int'],
    'x' => ['x', 'int'],
    'y' => ['y', 'int'],
  ];
  
  protected $staticAttributes = [
    ['type', 'int'],
    'color',
    //Number of minecarts on the tile:
    ['number', 'int'],
    ['light', 'bool'],
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
    $data['cost'] = $this->getCost();
    return $data;
  }

  /** Return the cost of the tile which is linked to its color*/
  public function getCost()
  {
    $nb = $this->getNumber();
    switch($this->getColor()){
      case YELLOW_COAL:
        return 1 * $nb;
      case BROWN_COAL:
        return 2 * $nb;
      case GREY_COAL:
        return 3 * $nb;
      case BLACK_COAL:
        return 4 * $nb;
    }
    return 0;
  }

  public function moveToPlayerBoard($player)
  {
    $this->setLocation(TILE_LOCATION_BOARD);
    $this->setState(TILE_STATE_VISIBLE);
    $this->setPId($player->getId());
    //TODO JSA compute tile COORD
    $this->setX(1);
    $this->setY(1);

    Notifications::giveTileTo($player, $this);
  }

}
