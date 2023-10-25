<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CoalBaron implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Mathieu Chatrain <EMAIL>
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
  // OPTION_BOARD_SIDE => [
  //   'name' => totranslate('Board Side'),
  //   'values' => [
  //     OPTION_A_SIDE => [
  //       'name' => totranslate('A-side'),
  //       'description' => totranslate('Play with A board side'),
  //       'tmdisplay' => totranslate('A-side'),
  //     ],
  //     OPTION_B_SIDE => [
  //       'name' => totranslate('B-side'),
  //       'description' => totranslate('Play with B board side'),
  //       'tmdisplay' => totranslate('B-side'),
  //     ],
  //   ],
  //   'default' => OPTION_A_SIDE,
  // ],
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
