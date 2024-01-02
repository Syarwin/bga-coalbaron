<?php
namespace COAL\States;

use COAL\Core\Notifications;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Managers\Players;
use COAL\Managers\Tiles;

trait EndGameScoringTrait
{
  function stEndGameScoring(){
    self::trace("stEndGameScoring()...");
    Notifications::endGameScoring();
    $this->computeEndGameScoring();
    $this->gamestate->nextState( 'next' );
  }
  
  /**
   * FINAL Scoring, see rules at p.16
   */
  function computeEndGameScoring(){
    self::trace("computeEndGameScoring()...");
    $players = Players::getAll();
    
    foreach($players as $pId => $player){
      /* 
        RULE 1
        For every 5 Francs that you return to the general bank note supply, you receive 1 VP. 
        Keep any Francs in excess of this as a tiebreaker.
      */
      $money = $player->getMoney();
      $moneyExcess = $money % 5;
      $moneyPoints = ($money - $moneyExcess) / 5 ;
      //TODO JSA Decrease money + update tiebreaker.
      if($moneyPoints != 0){
        $player->addPoints($moneyPoints);
        Notifications::endGameScoringMoney($player, $money, $moneyPoints);
      }

      /*
        RULE 2
        For every 3 coal cubes, you receive 1 VP. (It does not matter which
        color the cubes are or where they are placed : pit, pit cage, private coal
        storage, or outstanding orders.)
      */
      $nbCoals = Meeples::countPlayerCoals($pId);
      $nbExcessCoals = $nbCoals % 3;
      $coalsPoints = ($nbCoals - $nbExcessCoals) / 3 ;
      if($coalsPoints != 0){
        $player->addPoints($coalsPoints);
        Notifications::endGameScoringCoals($player, $nbCoals, $coalsPoints);
      }

      /*
      RULE 3 :For each order card left in your outstanding orders area, you lose 1 VP.
      */
      $nbPendingCards = Cards::countPlayerOrders($pId, CARD_LOCATION_OUTSTANDING);
      $cardsPoints = -1 * $nbPendingCards;
      if($cardsPoints != 0){
        $player->addPoints($cardsPoints);
        Notifications::endGameScoringCards($player, $nbPendingCards, $cardsPoints);
      }

      /*
      RULE 4 : For each tunnel tile that one side has in excess of the other side, you lose 2 VPs.
      */
      $nbLightTiles = Tiles::countPlayerTiles($pId,true);
      $nbDarkTiles = Tiles::countPlayerTiles($pId,false);
      $imbalance = abs($nbLightTiles - $nbDarkTiles);
      $balancePoints = -2 * $imbalance;
      if($balancePoints != 0){
        $player->addPoints($balancePoints);
        Notifications::endGameScoringBalance($player, $imbalance, $balancePoints);
      }
    }
  }
}
