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
/*
    "Visual" States Diagram :

        SETUP
        |
        v
        newDraft
        | 
        v
        draft   <------\
        |              |
        v              /
        draftNextPlayer
        | 
        v
    /-- nextShift   <-----------------------------------------\
    |     |                                                    |
    |     v                                                    |
    |   placeWorker <------------------------------------\     |
    |   |    |          |           |          |         |     |
    |   |    v          v           v          v         |     |
    |   |miningSteps chooseCard chooseTile -> chooseCoal |     |
    |   |    |          |           |          |         |     |
    |   v    v          v           v          v         /     |
    |    ----------------> nextPlayer  ------------------      |
    |                        |                                 |
    |                        v                                 |
    |                      endShift ---------------------------/
    |     
    \-> endGameScoring
        | 
        v
        preEndOfGame
        | 
        v
        END
*/

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => ['' => ST_DRAFT_INIT],
  ],

  ST_DRAFT_INIT => [
    'name' => 'newDraft',
    'description' => '',
    'type' => 'game',
    'action' => 'stNewDraft',
    'transitions' => [
      'next' => ST_DRAFT_PLAYER,
    ],
  ],

  ST_DRAFT_PLAYER => [
    'name' => 'draft',
    'description' => clienttranslate('${actplayer} must take 1 order card'),
    'descriptionmyturn' => clienttranslate('${you} must take 1 order card'),
    'args' => 'argDraft',
    'possibleactions' => ['actTakeCard'],
    'type' => 'activeplayer',
    'transitions' => [
      'next' => ST_DRAFT_NEXT_PLAYER,
      'zombiePass'=>ST_DRAFT_NEXT_PLAYER,
    ],
  ],

  ST_DRAFT_NEXT_PLAYER => [
    'name' => 'draftNextPlayer',
    'description' => '',
    'type' => 'game',
    'action' => 'stDraftNextPlayer',
    'transitions' => [
      'next' => ST_DRAFT_PLAYER,
      'end' => ST_NEXT_SHIFT,
    ],
  ],

  //I don't use the semantics "round" here because it has a different meaning in an (not planned) expansion 
  ST_NEXT_SHIFT => [
    'name' => 'nextShift',
    'description' => '',
    'type' => 'game',
    'action' => 'stNextShift',
    'updateGameProgression' => true,
    'transitions' => [
      'shift_start' => ST_PLACE_WORKER,
      'game_end' => ST_END_SCORING,
    ],
  ],

  ST_NEXT_PLAYER => [
    'name' => 'nextPlayer',
    'description' => '',
    'type' => 'game',
    'action' => 'stNextPlayer',
    'updateGameProgression' => true,
    'transitions' => [
      'next' => ST_PLACE_WORKER,
      'end_shift' => ST_END_SHIFT,
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
      'startMining' => ST_MINING,
      'chooseCard' => ST_CHOOSE_CARD,
      'chooseTile' => ST_CHOOSE_TILE,
      'chooseCoal' => ST_CHOOSE_COAL,
      'next' => ST_CONFIRM_CHOICES,
      'zombiePass'=>ST_CONFIRM_CHOICES,
    ],
  ],

  ST_MINING => [
    'name' => 'miningSteps',
    'description' => clienttranslate('${actplayer} may perform ${moves}/${totalMoves} work steps'),
    'descriptionmyturn' => clienttranslate('${you} may perform ${moves}/${totalMoves} work steps'),
    'args' => 'argMiningSteps',
    'type' => 'activeplayer',
    'possibleactions' => ['actMovePitCage', 'actMoveCoals', 'actStopMining', 'actRestart'],
    'transitions' => [
      'continue' => ST_MINING,
      'end' => ST_CONFIRM_CHOICES,
      'zombiePass'=>ST_CONFIRM_CHOICES,
    ],
  ],

  ST_CHOOSE_CARD => [
    'name' => 'chooseCard',
    'description' => clienttranslate('${actplayer} may choose to keep 1 card from the deck'),
    'descriptionmyturn' => clienttranslate('${you} may choose to keep 1 card from the deck'),
    'args' => 'argChooseCard',
    'type' => 'activeplayer',
    'possibleactions' => ['actChooseCard'],
    'transitions' => [
      'next' => ST_CONFIRM_CHOICES,
      'zombiePass'=>ST_CONFIRM_CHOICES,
    ],
  ],

  ST_CHOOSE_TILE => [
    'name' => 'chooseTile',
    'description' => clienttranslate('${actplayer} may choose to keep 1 tunnel tile from the deck'),
    'descriptionmyturn' => clienttranslate('${you} may choose to keep 1 tunnel tile from the deck'),
    'args' => 'argChooseTile',
    'type' => 'activeplayer',
    'possibleactions' => ['actChooseTile'],
    'transitions' => [
      'chooseCoal' => ST_CONFIRM_CHOICES,
      'next' => ST_CONFIRM_CHOICES,
      'zombiePass'=>ST_CONFIRM_CHOICES,
    ],
  ],

  ST_CHOOSE_COAL => [
    'name' => 'chooseCoal',
    'description' => clienttranslate('${actplayer} must choose the color of ${n} coals to get'),
    'descriptionmyturn' => clienttranslate('${you} must choose the color of ${n} coals to get'),
    'args' => 'argChooseCoal',
    'type' => 'activeplayer',
    'possibleactions' => ['actChooseCoal', 'actRestart'],
    'transitions' => [
      'next' => ST_CONFIRM_TURN,
      'zombiePass'=>ST_CONFIRM_TURN,
    ],
  ],


  ST_CONFIRM_CHOICES => [
    'name' => 'confirmChoices',
    'description' => '',
    'type' => 'game',
    'action' => 'stConfirmChoices',
    'transitions' => [
      '' => ST_CONFIRM_TURN,
    ],
  ],

  ST_CONFIRM_TURN => [
    'name' => 'confirmTurn',
    'description' => clienttranslate('${actplayer} must confirm or restart their turn'),
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'activeplayer',
    'args' => 'argsConfirmTurn',
    'action' => 'stConfirmTurn',
    'possibleactions' => ['actConfirmTurn', 'actRestart'],
    'transitions' => [
      'confirm' => ST_NEXT_PLAYER,
      'zombiePass'=>ST_NEXT_PLAYER,
    ],
  ],


  ST_END_SHIFT => [
    'name' => 'endShift',
    'description' => '',
    'type' => 'game',
    'action' => 'stEndShift',
    'transitions' => [
      'next' => ST_NEXT_SHIFT,
    ],
  ],

  ST_END_SCORING => [
    'name' => 'endGameScoring',
    'type' => 'game',
    'action' => 'stEndGameScoring',
    'transitions' => ['next' => ST_PRE_END_OF_GAME],
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
