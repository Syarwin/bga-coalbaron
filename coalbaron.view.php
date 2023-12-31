<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CoalBaron implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * coalbaron.view.php
 *
 */

require_once APP_BASE_PATH . 'view/common/game.view.php';

class view_coalbaron_coalbaron extends game_view
{
  protected function getGameName()
  {
    // Used for translations and stuff. Please do not modify.
    return 'coalbaron';
  }

  function build_page($viewArgs)
  {
  }
}
