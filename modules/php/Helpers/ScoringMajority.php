<?php

namespace COAL\Helpers;

use COAL\Core\Notifications;
use COAL\Helpers\ScoringMajorityPlace;
/*
 * ScoringMajority: all utility functions concerning the scoring computation
 */
class ScoringMajority
{ 
  /**
   * Type of element scored for this majority
   */
  public string $elementType;
  public string $elementTypeIndex;
  /**
   * Podium First place for this majority
   */
  public ScoringMajorityPlace $firstPlace;
  /**
   * Podium 2nd place for this majority 
   */
  public ScoringMajorityPlace $secondPlace;

  public function __construct(int $typeIndex, string $type, int $firstPlacePoints, int $secondPlacePoints)
  {
    $this->elementType = $type;
    $this->elementTypeIndex = $typeIndex;
    $this->firstPlace = new ScoringMajorityPlace($firstPlacePoints);
    $this->secondPlace = new ScoringMajorityPlace($secondPlacePoints);
  }

  /**
   * first players become second
   */
  public function move1to2Place(){
    $this->secondPlace->nbElements = $this->firstPlace->nbElements;
    //COPY player datas from 1st place to second place,(beware the 2nd reward is not affected !)
    $this->secondPlace->pIds = new Collection($this->firstPlace->pIds);
  }

  /**
   * return the number of scored players for this majority
   */
  public function doScore($players){
    $counter = $this->firstPlace->doScore($players,$this->elementType,$this->elementTypeIndex);
    if( $counter <=2 && count($players) >2){
      //IF >2 players : 2nd majority gives points
      //SECOND PLACE is not scored if first place has 2+ players
      $counter += $this->secondPlace->doScore($players,$this->elementType,$this->elementTypeIndex);
    }
    return $counter;
  }
  
  /**
   * Add player on podium if $nbr is enough compared to current podium
   */
  public function studyPlayer($pId,$nbr){
    if($nbr > $this->firstPlace->nbElements){//NEW MAX 1
      $this->move1to2Place();
      $this->firstPlace->nbElements = $nbr;
      $this->firstPlace->setPlayer($pId);
    }
    else if($nbr == $this->firstPlace->nbElements){//SAME MAX 1
      $this->firstPlace->addPlayer($pId);
    }
    else if($nbr > $this->secondPlace->nbElements){//NEW MAX 2
      $this->secondPlace->nbElements = $nbr;
      $this->secondPlace->setPlayer($pId);
    }
    else if($nbr == $this->secondPlace->nbElements){//SAME MAX 2
      $this->secondPlace->addPlayer($pId);
    }
  }
  
}
