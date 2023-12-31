<?php

namespace COAL\Helpers;

use ArrayObject;
use COAL\Core\Notifications;

/*
 * ScoringMajorityPlace: all utility functions concerning the scoring computation of 1 place on podium
 */

class ScoringMajorityPlace
{ 
  /**
   * Array of tied Players ids
   */
  public Collection $pIds;
  /** Number of elements counted to reach this place*/
  public int $nbElements;
  /** Points received for all players at this place */
  public int $reward;

  public function __construct(int $points)
  {
    $this->pIds = new Collection();
    $this->nbElements = 0;
    $this->reward = $points;
  }

  public function setPlayer($pId){
    //clear previous players + add that one
    $this->pIds = new Collection([$pId]);
  }
  public function addPlayer($pId){
    $this->pIds[] = $pId;
  }
  
  /**
   * return the number of scored players at this place
   */
  public function doScore($players,$type,$typeIndex){
    foreach($this->pIds as $pId){
      $player = $players[$pId];
      $player->addPoints($this->reward);
      Notifications::endShiftMajority($player, $this->reward, $type,$typeIndex, $this->nbElements);
    }
    return count($this->pIds);
  }
}
