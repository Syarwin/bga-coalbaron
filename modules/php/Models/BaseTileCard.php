<?php

namespace COAL\Models;

use COAL\Managers\Meeples;
use COAL\Managers\Tiles;

/*
 * BaseTileCard: 
 *  all utility functions concerning a base tile included in each player pit 
 * (so no need to save it in DB for now, the only moving elements are the 1 coals on it )
 */

class BaseTileCard
{ 
  /**
   * Player id
   */
  public string $pId;
  /**
   * Minecart color
   */
  public string $color;
  /**
   * Column in the pit
   */
  public int $x;
  /**
   * Row level in the pit
   */
  public int $y;

  public function __construct($pId,$color, $level, $column)
  {
    $this->pId = $pId;
    $this->color = $color;
    $this->y = $level;
    $this->x = $column;
  }

  public function getLocation(){
    return SPACE_PIT_TILE . "_".$this->y."_".$this->x;
  }

  /**
   * Only 1 minecart is drawn on base tiles
   * 
   * @return 0|1 0 if minecart is full, 1 if empty
   */
  public function countEmptyMinecarts(){
    $nbCoals = Meeples::countPlayerCoalsOnLocation($this->pId, $this->getLocation());
    if($nbCoals == 0 ) return 1;
    return 0;
  }

  public function getCoalColor()
  {
    switch($this->color){
      case MINECART_YELLOW:
        return YELLOW_COAL;
      case MINECART_BROWN:
        return BROWN_COAL;
      case MINECART_GREY:
        return GREY_COAL;
      case MINECART_BLACK:
        return BLACK_COAL;
    }
    return null;
  }
}
