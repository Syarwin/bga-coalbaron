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

    Notifications::moveCoalTo($player,$tile, $this);
  }
}
