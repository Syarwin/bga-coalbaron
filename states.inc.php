<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CoalBaron implementation : © Timothée Pecatte <tim.pecatte@gmail.com> & joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * CoalBaron game states description
 *
 */

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    // 'transitions' => ['' => ST_NEXT_ROUND],
    'transitions' => ['' => ST_PLACE_WORKER],
  ],

  2 => [
    'name' => 'foo',
    'description' => 'FOO',
    'descriptionmyturn' => 'FOO',
    'type' => 'activeplayer',
    'transitions' => [
      '' => ST_CONFIRM_CHOICES,
    ],
  ],

  ST_NEXT_ROUND => [
    'name' => 'nextRound',
    'description' => '',
    'type' => 'game',
    'action' => 'stNextRound',
    'updateGameProgression' => false,
    'transitions' => [
      // 'round_start' => ST_MOVE_AVATARS,
      'game_end' => ST_PRE_END_OF_GAME,
    ],
  ],

  ST_NEXT_PLAYER => [
    'name' => 'nextPlayer',
    'description' => '',
    'type' => 'game',
    'action' => 'stNextPlayer',
    'updateGameProgression' => false,
    'transitions' => [
      'next' => ST_PLACE_WORKER,
      // 'end_turn' => ST_MOVE_AVATARS,
    ],
  ],

  ST_PLACE_WORKER => [
    'name' => 'placeWorker',
    'description' => clienttranslate('${actplayer} must place their worker(s)'),
    'descriptionmyturn' => clienttranslate('${you} must place your worker(s)'),
    'args' => 'argPlaceWorker',
    'type' => 'activeplayer',
    'possibleactions' => ['actPlaceWorker'],
    'transitions' => [
      //'' => ST_CONFIRM_CHOICES,
      'startMining' => ST_MINING,
      'next' => ST_NEXT_PLAYER,
    ],
  ],
  
  ST_MINING => [
    'name' => 'miningSteps',
    'description' => clienttranslate('${actplayer} may perform ${moves} work steps'),
    'descriptionmyturn' => clienttranslate('${you} may perform ${moves} work steps'),
    'args' => 'argMiningSteps',
    'type' => 'activeplayer',
    'possibleactions' => ['actMovePitCage', 'actMoveCoals'],
    'transitions' => [
      'continue' => ST_MINING,
      'end' => ST_CONFIRM_CHOICES,
    ],
  ],

  ST_CONFIRM_CHOICES => [
    'name' => 'confirmChoices',
    'description' => '',
    'type' => 'game',
    'action' => 'stConfirmChoices',
    'transitions' => [
      '' => ST_NEXT_PLAYER,
    ],
  ],

  ST_PRE_END_OF_GAME => [
    'name' => 'preEndOfGame',
    'type' => 'game',
    'action' => 'stPreEndOfGame',
    'transitions' => ['' => ST_END_GAME],
  ],

  // Final state.
  // Please do not modify (and do not overload action/args methods).
  ST_END_GAME => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd',
  ],
];
