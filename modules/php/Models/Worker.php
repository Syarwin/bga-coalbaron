<?php

namespace COAL\Models;

/*
 * Worker: all utility functions concerning a worker meeple
 */

class Worker extends \COAL\Models\Meeple
{ 

    public function getType()
    {
      return WORKER;
    }
}
