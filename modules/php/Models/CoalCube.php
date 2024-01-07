<?php

namespace COAL\Models;

use COAL\Core\Notifications;

/*
 * CoalCube: all utility functions concerning a Coal cube
 */

class CoalCube extends \COAL\Models\Meeple
{ 

  public function getUiData()
  {
    $data = parent::getUiData();
    
    $cardDatas = $this->getCoalOnCardInfos(false);
    if(isset($cardDatas['spotIndex'])) $data['cardSpotIndex'] = $cardDatas['spotIndex'];
    if(isset($cardDatas['cardId'])) $data['cardId'] = $cardDatas['cardId'];

    return $data;
  }
  /**
   * Return the position of the coal when placed on an order Card : from 0 to N-1
   * @return int
   */
  public function getCoalSpotIndexOnCard()
  {
    return $this->getCoalOnCardInfos()['spotIndex'];
  }

  /**
   * Return the position of the coal when placed on an order Card : from 0 to N-1
   *  and the card id
   * @return array format ['cardId' => 55,'spotIndex' => 2]
   */
  public function getCoalOnCardInfos($errorIfNotOnCard = true)
  {
    //COAL_LOCATION_CARD.$cardId."_".$index
    $location = $this->getLocation();
    if (preg_match("/^".COAL_LOCATION_CARD."(?P<cardId>\d+)_(?P<spotIndex>(-)*\d+)$/", $location, $matches ) == 1) {
      return [
        'cardId' => intval($matches['cardId']), 
        'spotIndex' => intval($matches['spotIndex']), 
      ];
    }
    if($errorIfNotOnCard) throw new \feException("Coal cube is not placed on a card: $location");
    return [];
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
