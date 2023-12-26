<?php

namespace COAL\Models;

use COAL\Core\Notifications;

/*
 * CoalCube: all utility functions concerning a Coal cube
 */

class CoalCube extends \COAL\Models\Meeple
{ 

  public function moveToTile($player,$tile)
  {
    $this->setLocation(COAL_LOCATION_TILE.$tile->getId());
    $this->setPId($tile->getPId());

    Notifications::moveCoalToTile($player,$tile, $this);
  }
  
  public function moveToCage($player)
  {
    //pid must be already known because coals are owned before going to cage
    $this->setLocation(SPACE_PIT_CAGE);
  }
  public function moveToStorage($player)
  {
    //pid must be already known because coals are owned before going to cage
    $this->setLocation(COAL_LOCATION_STORAGE);
  }
}
