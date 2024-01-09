<?php

namespace COAL\Models;

use COAL\Core\Globals;
use COAL\Core\Stats;
use COAL\Core\Notifications;
use COAL\Core\Preferences;
use COAL\Managers\Tiles;
use COAL\Managers\Cards;
use COAL\Managers\Players;
use COAL\Managers\Meeples;

/*
 * Player: all utility functions concerning a player
 */

class Player extends \COAL\Helpers\DB_Model
{
  private $map = null;
  protected $table = 'player';
  protected $primary = 'player_id';
  protected $attributes = [
    'id' => ['player_id', 'int'],
    'no' => ['player_no', 'int'],
    'name' => 'player_name',
    'color' => 'player_color',
    'eliminated' => 'player_eliminated',
    'score' => ['player_score', 'int'],
    'scoreAux' => ['player_score_aux', 'int'],
    'money' => ['money', 'int'],
    'cageLevel' => ['cage_level', 'int'],
    'zombie' => 'player_zombie',
  ];

  public function getUiData($currentPlayerId = null)
  {
    $data = parent::getUiData();
    $current = $this->id == $currentPlayerId;
    $data['workers'] = Meeples::getNbAvailableWorkers($this);
    $data['ordersTodo'] = Cards::countPlayerOrders($this->id, CARD_LOCATION_OUTSTANDING);
    $data['ordersDone'] = Cards::countPlayerOrders($this->id, CARD_LOCATION_DELIVERED);
    $data['coals'] = Meeples::countPlayerCoals($this->id);
    $data['imbalance'] = $this->getTunnelImbalance();

    return $data;
  }

  public function getPref($prefId)
  {
    return Preferences::get($this->id, $prefId);
  }

  public function getStat($name)
  {
    $name = 'get' . \ucfirst($name);
    return Stats::$name($this->id);
  }
  
  public function addPoints($points)
  {
    $this->setScore( $this->getScore() + $points);
    Stats::inc( "score", $this->id, $points );
  }

  public function movePitCageTo($toLevel)
  {
    $from = $this->getCageLevel();
    if ($from == $toLevel) {
      throw new \BgaVisibleSystemException('Incorrect destination : your pit cage must move to a different level');
    }
    $this->setCageLevel($toLevel);
    Globals::incMiningMoves(-1);
    Notifications::movePitCage($this, $from, $toLevel);
  }
  
  /**
   * @return int absolute number of tunnel tiles in excess on LIGHT side VS DARK side
   */
  public function getTunnelImbalance()
  {
    $nbLightTiles = Tiles::countPlayerTiles($this->id,true);
    $nbDarkTiles = Tiles::countPlayerTiles($this->id,false);
    return abs($nbLightTiles - $nbDarkTiles);
  }
}
