<?php

/*
 * Game Constants
 */

const YELLOW_COAL = 'yellow_coal';
const BLACK_COAL = 'black_coal';
const GREY_COAL = 'grey_coal';
const BROWN_COAL = 'brown_coal';
const WORKER = 'worker';
const CARDS_START_NB = 3;//3 per player
const TILES_EACH_NB = 3; //3 tiles of each

/** Worker spaces */
const SPACE_FACTORY = 'factory';
const SPACE_FACTORY_1 = 'factory_1';
const SPACE_FACTORY_2 = 'factory_2';
const SPACE_FACTORY_3 = 'factory_3';
const SPACE_FACTORY_4 = 'factory_4';
const SPACE_FACTORY_5 = 'factory_5';
const SPACE_FACTORY_6 = 'factory_6';
const SPACE_FACTORY_7 = 'factory_7';
const SPACE_FACTORY_8 = 'factory_8';
const SPACE_FACTORY_DRAW = 'factory_draw';
const SPACE_MINING = 'mining';
const SPACE_MINING_4 = 'mining_4';
const SPACE_MINING_6 = 'mining_6';
const SPACE_MINING_8 = 'mining_8';
const SPACE_MINING_10 = 'mining_10';
const SPACE_DELIVERY = 'delivery';
const SPACE_DELIVERY_BARROW = 'delivery_1';
const SPACE_DELIVERY_CARRIAGE= 'delivery_2';
const SPACE_DELIVERY_MOTORCAR = 'delivery_3';
const SPACE_DELIVERY_ENGINE = 'delivery_4';
const SPACE_BANK = 'bank';
const SPACE_BANK_1 = 'bank_1';
const SPACE_BANK_3 = 'bank_3';
const SPACE_BANK_4 = 'bank_4';
const SPACE_BANK_5 = 'bank_5';
const SPACE_BANK_6 = 'bank_6';
const SPACE_ORDER = 'order';
const SPACE_ORDER_1 = 'order_1';
const SPACE_ORDER_2 = 'order_2';
const SPACE_ORDER_3 = 'order_3';
const SPACE_ORDER_4 = 'order_4';
const SPACE_ORDER_DRAW = 'order_draw';

const SPACE_RESERVE = "reserve";
const SPACE_CANTEEN = "canteen";

const SPACE_PIT_CAGE = "pit_cage";
const SPACE_PIT_TILE = "pit_tile";//TO  be followed by "_y_x" with coordinates y (row) and x (col) 

/* CARDS TYPES */
const TRANSPORT_BARROW = "barrow";
const TRANSPORT_CARRIAGE = "carriage";
const TRANSPORT_MOTORCAR = "motorcar";
const TRANSPORT_ENGINE = "engine";

/* CARDS LOCATIONS */
const CARD_LOCATION_DECK = "deck";
const CARD_LOCATION_OUTSTANDING = "outstanding";
const TILE_LOCATION_DECK = "deck";
const TILE_LOCATION_BOARD = "player";
const TILE_STATE_VISIBLE = 1;

/*
 * Game options
 */

/*
 * User preferences
 */
const OPTION_CONFIRM = 103;
const OPTION_CONFIRM_DISABLED = 0;
const OPTION_CONFIRM_TIMER = 1;
const OPTION_CONFIRM_ENABLED = 2;

/*
 * State constants
 */
const ST_GAME_SETUP = 1;

const ST_NEXT_ROUND = 5;

const ST_NEXT_PLAYER = 10;
const ST_PLACE_WORKER = 11;

const ST_CONFIRM_CHOICES = 20;

const ST_PRE_END_OF_GAME = 98;
const ST_END_GAME = 99;

/******************
 ****** STATS ******
 ******************/
