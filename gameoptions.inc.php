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
 * gameoptions.inc.php
 *
 * CoalBaron game options description
 *
 */

namespace COAL;

require_once 'modules/php/constants.inc.php';

$game_options = [
  OPTION_CARDS_VISIBILITY => [
    'name' => totranslate('Delivered cards visibility'),
    'values' => [
      OPTION_VISIBLE_PLAYER_ONLY => [
        'name' => totranslate('Player only'),
        'description' => totranslate('Each player can see their own delivered cards'),
        'tmdisplay' => totranslate('Player cards only'),
      ],
      OPTION_VISIBLE_ALL => [
        'name' => totranslate('All'),
        'description' => totranslate('Everyone can see all delivered cards'),
        'tmdisplay' => totranslate('All players cards'),
      ],
    ],
    'default' => OPTION_VISIBLE_PLAYER_ONLY,
  ],
];

$game_preferences = [
  OPTION_CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'values' => [
      OPTION_CONFIRM_TIMER => [
        'name' => totranslate('Enabled with timer'),
      ],
      OPTION_CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      OPTION_CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],
];
