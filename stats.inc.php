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

    "turnOrder1" => array("id"=> 11,
      "name" => totranslate("Shift 1 - Turn order"),
      "type" => "int" ),
    "turnOrder2" => array("id"=> 12,
      "name" => totranslate("Shift 2 - Turn order"),
      "type" => "int" ),
    "turnOrder3" => array("id"=> 13,
      "name" => totranslate("Shift 3 - Turn order"),
      "type" => "int" ),

    "nbActions" => array("id"=> 14,
      "name" => totranslate("Actions done"),
      "type" => "int" ),
    "nbActions1" => array("id"=> 15,
      "name" => totranslate("Minecarts factory actions done"),
      "type" => "int" ),
    "nbActions2" => array("id"=> 16,
      "name" => totranslate("Mining actions done"),
      "type" => "int" ),
    "nbActions3" => array("id"=> 17,
      "name" => totranslate("Delivery actions done"),
      "type" => "int" ),
    "nbActions4" => array("id"=> 18,
      "name" => totranslate("Money actions done"),
      "type" => "int" ),
    "nbActions5" => array("id"=> 19,
      "name" => totranslate("New order actions done"),
      "type" => "int" ),   
    
    "cardsReceived" => array("id"=> 30,
      "name" => totranslate("Cards received"),
      "type" => "int" ),
    "cardsDrawn" => array("id"=> 31,
      "name" => totranslate("Cards taken from the deck"),
      "type" => "int" ),
    "cardsDelivered" => array("id"=> 32,
      "name" => totranslate("Cards delivered"),
      "type" => "int" ),
      
    "moneyReceived" => array("id"=> 33,
      "name" => totranslate("Money received"),
      "type" => "int" ),
    "moneySpent" => array("id"=> 34,
      "name" => totranslate("Money spent"),
      "type" => "int" ),
    "moneyLeft" => array("id"=> 35,
      "name" => totranslate("Money at the end"),
      "type" => "int" ),

    "tilesReceived" => array("id"=> 36,
      "name" => totranslate("Tiles received"),
      "type" => "int" ),
    "tilesDrawn" => array("id"=> 37,
      "name" => totranslate("Tiles taken from the deck"),
      "type" => "int" ),
    "tilesLight" => array("id"=> 38,
      "name" => totranslate("Light tiles"),
      "type" => "int" ),
    "tilesDark" => array("id"=> 39,
      "name" => totranslate("Dark tiles"),
      "type" => "int" ),
      
    "coalsReceived" => array("id"=> 40,
      "name" => totranslate("Coals received"),
      "type" => "int" ),
    YELLOW_COAL."Received" => array("id"=> 41,
      "name" => totranslate("Yellow coals received"),
      "type" => "int" ),
    BROWN_COAL."Received" => array("id"=> 42,
      "name" => totranslate("Brown coals received"),
      "type" => "int" ),
    GREY_COAL."Received" => array("id"=> 43,
      "name" => totranslate("Grey coals received"),
      "type" => "int" ),
    BLACK_COAL."Received" => array("id"=> 44,
      "name" => totranslate("Black coals received"),
      "type" => "int" ),
    "coalsPlacedOnCard" => array("id"=> 45,
      "name" => totranslate("Coals placed on order cards"),
      "type" => "int" ),
    "coalsDelivered" => array("id"=> 46,
      "name" => totranslate("Coals delivered with order cards"),
      "type" => "int" ),
    "coalsLeft" => array("id"=> 47,
      "name" => totranslate("Coals remaining"),
      "type" => "int" ),
      
    "miningMovesReceived" => array("id"=> 50,
      "name" => totranslate("Mining work steps received"),
      "type" => "int" ),
    "miningMovesDone" => array("id"=> 51,
      "name" => totranslate("Mining work steps done"),
      "type" => "int" ),
   ],
];
