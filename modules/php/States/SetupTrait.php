<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Engine;
use COAL\Core\Stats;
use COAL\Core\Preferences;
use COAL\Managers\Players;
use COAL\Managers\Tiles;
use COAL\Managers\Cards;
use COAL\Managers\Meeples;
use COAL\Models\Player;

trait SetupTrait
{
  protected function setupNewGame($players, $options = [])
  {
    Players::setupNewGame($players, $options);
    Globals::setupNewGame($players, $options);
    Preferences::setupNewGame($players, $this->player_preferences);
    Stats::checkExistence();
    $initialMoney = Players::getInitialMoney(count($players));
    foreach ($players as $pId => $player) {
      Stats::inc( "moneyReceived", $pId, $initialMoney );
      Stats::inc( "moneyLeft", $pId, $initialMoney );
    }
    Tiles::setupNewGame($players, $options);
    Cards::setupNewGame($players, $options);
    Meeples::setupNewGame($players, $options);

    $this->setGameStateInitialValue('logging', true);
    $this->activeNextPlayer();
  }
}
