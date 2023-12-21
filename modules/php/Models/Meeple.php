<?php

namespace COAL\Models;

/*
 * Meeple: all utility functions concerning a Meeple 
 */

class Meeple extends \COAL\Helpers\DB_Model
{
  protected $table = 'meeples';
  protected $primary = 'meeple_id';
  protected $attributes = [
    'id' => ['meeple_id', 'int'],
    'state' => ['meeple_state', 'int'],
    'location' => 'meeple_location',
    'pId' => ['player_id', 'int'],
  ];
  
  protected $staticAttributes = [
    ['type', 'int'],
  ];

  public function __construct($row, $datas)
  {
    parent::__construct($row);
    foreach ($datas as $attribute => $value) {
      $this->$attribute = $value;
    }
  }

  public function getUiData()
  {
    $data = parent::getUiData();
    return $data;
  }
 
}
