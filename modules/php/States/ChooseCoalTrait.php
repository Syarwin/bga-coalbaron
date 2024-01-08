<?php
namespace COAL\States;

use COAL\Core\Globals;
use COAL\Exceptions\MissingCoalException;
use COAL\Managers\Meeples;
use COAL\Managers\Players;
use COAL\Managers\Tiles;

/**
 * States actions about choosing a coal color
 */
trait ChooseCoalTrait
{ 

  function argChooseCoal()
  {
    $coalsInReserve = Meeples::countAvailableCoalsColorArray();

    return array(
      'coalsInReserve' => $coalsInReserve,
      'n' => Globals::getNbCoalsToChoose(),
      'tile' => Globals::getTileCoalsToChoose(),
    );
  }

  function actChooseCoal($colorsArray){
    self::checkAction( 'actChooseCoal' ); 
    self::trace("actChooseCoal()");

    $player = Players::getActive();
    $tileId = Globals::getTileCoalsToChoose();
    $nbToChoose = Globals::getNbCoalsToChoose();

    //CHECK INPUT : 
    if(count($colorsArray) != $nbToChoose){
      throw new \BgaVisibleSystemException("Wrong number of colors : $nbToChoose needed");
    }

    $tile = Tiles::get($tileId);
    foreach($colorsArray as $color){
      if(!in_array($color,COAL_TYPES)){
        throw new \BgaVisibleSystemException("Not supported color : $color");
      }
      $coals = Meeples::placeAnyCoalOnTile($player, $tile, $color, 1);
      if (count($coals) == 0) {
        throw new MissingCoalException("Missing coal ($color) for tile $tileId");
      }
    }

    $this->gamestate->nextState('next');
  }
   
}
