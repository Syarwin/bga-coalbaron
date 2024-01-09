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
 * stats.inc.php
 *
 * CoalBaron game statistics description
 *
 */

require_once 'modules/php/constants.inc.php';

$stats_type = [
  'table' => [],

  'value_labels' => [],

  'player' => [ 
    //A score stat to enable comparison with average player's score
    "score" => array("id"=> 10,
      "name" => totranslate("Score"),
      "type" => "int" ),

   ],
];
