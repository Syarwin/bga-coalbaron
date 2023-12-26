<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CoalBaron implementation : Timothée Pecatte <tim.pecatte@gmail.com>, joesimpson <1324811+joesimpson@users.noreply.github.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * coalbaron.action.php
 *
 * CoalBaron main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/coalbaron/coalbaron/myAction.html", ...)
 *
 */

class action_coalbaron extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = 'common_notifwindow';
      $this->viewArgs['table'] = self::getArg('table', AT_posint, true);
    } else {
      $this->view = 'coalbaron_coalbaron';
      self::trace('Complete reinitialization of board game');
    }
  }

  public function actPlaceWorker()
  {
    self::setAjaxMode();
    $spaceId = self::getArg('spaceId', AT_alphanum, true);
    $this->game->actPlaceWorker($spaceId);
    self::ajaxResponse();
  }
  
  public function actMovePitCage()
  {
    self::setAjaxMode();
    $toLevel = self::getArg('level', AT_posint, true);
    $this->game->actMovePitCage($toLevel);
    self::ajaxResponse();
  }
  public function actMoveCoals()
  {
    self::setAjaxMode();
    $spaceId = self::getArg('spaceId', AT_alphanum, true);
    // ---------- ----------  array of COALS'ids  --------------------
    $coal_ids_raw = self::getArg( "coalId", AT_numberlist, true );
    // Removing last ';' if exists
    if( substr( $coal_ids_raw, -1 ) == ';' )
       $coal_ids_raw = substr( $coal_ids_raw, 0, -1 );
    if( $coal_ids_raw == '' )
        $coalIdArray = array();
    else
        $coalIdArray = explode( ';', $coal_ids_raw );
    // ---------- ---------- -------------------- --------------------
    $this->game->actMoveCoals($coalIdArray,$spaceId);
    self::ajaxResponse();
  }


  ///////////////////
  /////  PREFS  /////
  ///////////////////

  public function actChangePref()
  {
    self::setAjaxMode();
    $pref = self::getArg('pref', AT_posint, false);
    $value = self::getArg('value', AT_posint, false);
    $this->game->actChangePreference($pref, $value);
    self::ajaxResponse();
  }

  //////////////////
  ///// UTILS  /////
  //////////////////
  public function validateJSonAlphaNum($value, $argName = 'unknown')
  {
    if (is_array($value)) {
      foreach ($value as $key => $v) {
        $this->validateJSonAlphaNum($key, $argName);
        $this->validateJSonAlphaNum($v, $argName);
      }
      return true;
    }
    if (is_int($value)) {
      return true;
    }
    $bValid = preg_match('/^[_0-9a-zA-Z- ]*$/', $value) === 1;
    if (!$bValid) {
      throw new feException("Bad value for: $argName", true, true, FEX_bad_input_argument);
    }
    return true;
  }
}
