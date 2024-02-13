<?php

namespace COAL\Core;

use COAL\Core\Game;
/*
 * Globals
 */

class Globals extends \COAL\Helpers\DB_Manager
{
  protected static $initialized = false;
  protected static $variables = [
    'turn' => 'int',
    'shift' => 'int',
    'firstPlayer' => 'int',

    'miningMoves' => 'int',
    'miningMovesTotal' => 'int',

    'nbCoalsToChoose' => 'int',
    'tileCoalsToChoose' => 'int',

    // -- SHIFT 1
    'majorityWinners1_1_1'  => 'obj',
    'majorityWinners2_1_1'  => 'obj',
    'majorityWinners3_1_1'  => 'obj',
    'majorityWinners4_1_1'  => 'obj',
    'majorityWinners5_1_1'  => 'obj',
    'majorityWinners6_1_1'  => 'obj',
    'majorityWinners7_1_1'  => 'obj',
    'majorityWinners8_1_1'  => 'obj',
    'majorityWinners9_1_1'  => 'obj',
    'majorityWinners10_1_1' => 'obj',
    'majorityWinners11_1_1' => 'obj',
    'majorityWinners12_1_1' => 'obj',
    'majorityWinners1_2_1'  => 'obj',
    'majorityWinners2_2_1'  => 'obj',
    'majorityWinners3_2_1'  => 'obj',
    'majorityWinners4_2_1'  => 'obj',
    'majorityWinners5_2_1'  => 'obj',
    'majorityWinners6_2_1'  => 'obj',
    'majorityWinners7_2_1'  => 'obj',
    'majorityWinners8_2_1'  => 'obj',
    'majorityWinners9_2_1'  => 'obj',
    'majorityWinners10_2_1' => 'obj',
    'majorityWinners11_2_1' => 'obj',
    'majorityWinners12_2_1' => 'obj',

    // -- SHIFT 2
    'majorityWinners1_1_2'  => 'obj',
    'majorityWinners2_1_2'  => 'obj',
    'majorityWinners3_1_2'  => 'obj',
    'majorityWinners4_1_2'  => 'obj',
    'majorityWinners5_1_2'  => 'obj',
    'majorityWinners6_1_2'  => 'obj',
    'majorityWinners7_1_2'  => 'obj',
    'majorityWinners8_1_2'  => 'obj',
    'majorityWinners9_1_2'  => 'obj',
    'majorityWinners10_1_2' => 'obj',
    'majorityWinners11_1_2' => 'obj',
    'majorityWinners12_1_2' => 'obj',
    'majorityWinners1_2_2'  => 'obj',
    'majorityWinners2_2_2'  => 'obj',
    'majorityWinners3_2_2'  => 'obj',
    'majorityWinners4_2_2'  => 'obj',
    'majorityWinners5_2_2'  => 'obj',
    'majorityWinners6_2_2'  => 'obj',
    'majorityWinners7_2_2'  => 'obj',
    'majorityWinners8_2_2'  => 'obj',
    'majorityWinners9_2_2'  => 'obj',
    'majorityWinners10_2_2' => 'obj',
    'majorityWinners11_2_2' => 'obj',
    'majorityWinners12_2_2' => 'obj',
    // -- SHIFT 3
    'majorityWinners1_1_3'  => 'obj',
    'majorityWinners2_1_3'  => 'obj',
    'majorityWinners3_1_3'  => 'obj',
    'majorityWinners4_1_3'  => 'obj',
    'majorityWinners5_1_3'  => 'obj',
    'majorityWinners6_1_3'  => 'obj',
    'majorityWinners7_1_3'  => 'obj',
    'majorityWinners8_1_3'  => 'obj',
    'majorityWinners9_1_3'  => 'obj',
    'majorityWinners10_1_3' => 'obj',
    'majorityWinners11_1_3' => 'obj',
    'majorityWinners12_1_3' => 'obj',
    'majorityWinners1_2_3'  => 'obj',
    'majorityWinners2_2_3'  => 'obj',
    'majorityWinners3_2_3'  => 'obj',
    'majorityWinners4_2_3'  => 'obj',
    'majorityWinners5_2_3'  => 'obj',
    'majorityWinners6_2_3'  => 'obj',
    'majorityWinners7_2_3'  => 'obj',
    'majorityWinners8_2_3'  => 'obj',
    'majorityWinners9_2_3'  => 'obj',
    'majorityWinners10_2_3' => 'obj',
    'majorityWinners11_2_3' => 'obj',
    'majorityWinners12_2_3' => 'obj',


    // Game options
    'cardsVisibility' => 'int',
  ];

  /*
   * Setup new game
   */
  public static function setupNewGame($players, $options)
  {
    self::setTurn(0);
    self::setShift(0);

    self::setCardsVisibility($options[OPTION_CARDS_VISIBILITY]);

    foreach ($players as $pId => $player) {
      if ($player['player_table_order'] == 1) {
        self::setFirstPlayer($pId);
        break;
      }
    }
    self::initAllMajorityWinners();
  }

  public static function saveMajorityWinners($playerIds, $typeIndex, $gameRound, $podiumIndex)
  {
    $globalvar = "majorityWinners$typeIndex" . "_$podiumIndex" . "_$gameRound";
    //self::$variables["$globalvar"] = 'obj';
    $setterName = "set" . ucfirst($globalvar);
    self::$setterName($playerIds);
  }

  public static function getAllMajorityWinners()
  {
    $values = [];
    foreach (self::$variables as $name => $type) {
      if (str_starts_with($name, "majorityWinners")) {
        $getter = "get" . ucfirst($name);
        $values[substr($name, 15)] = self::$getter();
      }
    }
    return $values;
  }

  public static function initAllMajorityWinners()
  {
    foreach (self::$variables as $name => $type) {
      if (str_starts_with($name, "majorityWinners")) {
        $setter = "set" . ucfirst($name);
        self::$setter([]);
      }
    }
  }

  protected static $table = 'global_variables';
  protected static $primary = 'name';
  protected static function cast($row)
  {
    $val = json_decode(\stripslashes($row['value']), true);
    return self::$variables[$row['name']] == 'int' ? ((int) $val) : $val;
  }

  /*
   * Fetch all existings variables from DB
   */
  protected static $data = [];
  public static function fetch()
  {
    // Turn of LOG to avoid infinite loop (Globals::isLogging() calling itself for fetching)
    $tmp = self::$log;
    self::$log = false;

    foreach (self::DB()
      ->select(['value', 'name'])
      ->get(false)
      as $name => $variable) {
      if (\array_key_exists($name, self::$variables)) {
        self::$data[$name] = $variable;
      }
    }
    self::$initialized = true;
    self::$log = $tmp;
  }

  /*
   * Create and store a global variable declared in this file but not present in DB yet
   *  (only happens when adding globals while a game is running)
   */
  public static function create($name)
  {
    if (!\array_key_exists($name, self::$variables)) {
      return;
    }

    $default = [
      'int' => 0,
      'obj' => [],
      'bool' => false,
      'str' => '',
    ];
    $val = $default[self::$variables[$name]];
    self::DB()->insert(
      [
        'name' => $name,
        'value' => \json_encode($val),
      ],
      true
    );
    self::$data[$name] = $val;
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (!self::$initialized) {
      self::fetch();
    }

    if (preg_match('/^([gs]et|inc|is)([A-Z])(.*)$/', $method, $match)) {
      // Sanity check : does the name correspond to a declared variable ?
      $name = strtolower($match[2]) . $match[3];
      if (!\array_key_exists($name, self::$variables)) {
        throw new \InvalidArgumentException("Property {$name} doesn't exist");
      }

      // Create in DB if don't exist yet
      if (!\array_key_exists($name, self::$data)) {
        self::create($name);
      }

      if ($match[1] == 'get') {
        // Basic getters
        return self::$data[$name];
      } elseif ($match[1] == 'is') {
        // Boolean getter
        if (self::$variables[$name] != 'bool') {
          throw new \InvalidArgumentException("Property {$name} is not of type bool");
        }
        return (bool) self::$data[$name];
      } elseif ($match[1] == 'set') {
        // Setters in DB and update cache
        $value = $args[0];
        if (self::$variables[$name] == 'int') {
          $value = (int) $value;
        }
        if (self::$variables[$name] == 'bool') {
          $value = (bool) $value;
        }

        self::$data[$name] = $value;
        self::DB()->update(['value' => \addslashes(\json_encode($value))], $name);
        return $value;
      } elseif ($match[1] == 'inc') {
        if (self::$variables[$name] != 'int') {
          throw new \InvalidArgumentException("Trying to increase {$name} which is not an int");
        }

        $getter = 'get' . $match[2] . $match[3];
        $setter = 'set' . $match[2] . $match[3];
        return self::$setter(self::$getter() + (empty($args) ? 1 : $args[0]));
      }
    }
    return undefined;
  }
}
