<?php

namespace COAL\Models;

use COAL\Core\Notifications;
use COAL\Managers\Tiles;

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
  
  /** Return the row of the tile which is linked to its color*/
  public function getRow()
  {
    switch($this->getColor()){
      case YELLOW_COAL:
        return 1;
      case BROWN_COAL:
        return 2;
      case GREY_COAL:
        return 3;
      case BLACK_COAL:
        return 4;
    }
    return 0;
  }
  public function getMinecartColor()
  {
    switch($this->getColor()){
      case YELLOW_COAL:
        return MINECART_YELLOW;
      case BROWN_COAL:
        return MINECART_BROWN;
      case GREY_COAL:
        return MINECART_GREY;
      case BLACK_COAL:
        return MINECART_BLACK;
    }
    return null;
  }

  public function moveToPlayerBoard($player,$column)
  {
    $this->setLocation(TILE_LOCATION_BOARD);
    $this->setState(TILE_STATE_VISIBLE);
    $this->setPId($player->getId());
    $this->setX($column);
    $this->setY($this->getRow());

    Notifications::giveTileTo($player, $this);
  }

  /**
   * return the number of empty minecarts (the opposite of number of cubes on the tile)
   */
  public function countEmptyMinecarts()
  {
    $counter = 0;
    $coalsStatus = Tiles::getTileCoalsStatus($this);
    foreach( $coalsStatus as $coalStatus){
      foreach( $coalStatus as $color => $status){
        if($status == COAL_EMPTY_SPOT) $counter++;
      }
    }
    return $counter;
  }
}
