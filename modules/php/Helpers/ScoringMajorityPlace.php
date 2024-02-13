<?php

namespace COAL\Helpers;

use ArrayObject;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Stats;

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
   * @param Collection $players
   * @param string $type
   * @param int $typeIndex
   * @param int $gameRound game round when this score is done
   * @param int $podiumIndex
   * @return int the number of scored players at this place
   */
  public function doScore($players,$type,$typeIndex, $gameRound = 1, $podiumIndex){
    foreach($this->pIds as $pId){
      $player = $players[$pId];
      $player->addPoints($this->reward);
      Notifications::endShiftMajority($player, $this->reward, $type,$typeIndex, $this->nbElements);
      $setterStatName = "setScoreMajority$typeIndex"."_$gameRound";
      Stats::$setterStatName( $player, $this->reward );
    }
    Globals::saveMajorityWinners($this->pIds, $typeIndex, $gameRound, $podiumIndex );
    return count($this->pIds);
  }
}
