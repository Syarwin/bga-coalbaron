<?php
namespace COAL\Core;
use CoalBaron;

/*
 * Game: a wrapper over table object to allow more generic modules
 */
class Game
{
  public static function get()
  {
    return CoalBaron::get();
  }
}
