<?php

namespace COAL\Models;

use COAL\Core\Notifications;

/*
 * CoalCube: all utility functions concerning a Coal cube
 */

class CoalCube extends \COAL\Models\Meeple
{ 

  /**
   * Return the position of the coal when placed on an order Card : from 0 to N-1
   */
  public function getCoalSpotIndexOnCard()
  {
    //COAL_LOCATION_CARD.$cardId."_".$index
    $location = $this->getLocation();
    if (preg_match("/^".COAL_LOCATION_CARD."(?P<cardId>\d+)_(?P<spotIndex>(-)*\d+)$/", $location, $matches ) == 1) {
      return $matches['spotIndex'];
    }
    throw new \feException("Coal cube is not placed on a card: $location");
  }

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
  public function moveToCard($cardId, $spotIndex)
  {
    //pid must be already known because coals are owned before going to cage
    $this->setLocation(COAL_LOCATION_CARD.$cardId.'_'.$spotIndex);
  }
}
