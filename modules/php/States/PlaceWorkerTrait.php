<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Engine;
use COAL\Core\Stats;
use COAL\Helpers\Utils;
use COAL\Managers\Meeples;
use COAL\Managers\Players;
use COAL\Managers\Tiles;

trait PlaceWorkerTrait
{
    use WorkerAtBankTrait;
    use WorkerAtDeliveryTrait;
    use WorkerAtFactoryTrait;
    use WorkerAtMiningTrait;
    use WorkerAtOrderTrait;

    /**
     * List all possible Worker Spaces to play by player $pId
     */
    function getPossibleSpaces($pId, $nbPlayers) {
        self::trace("getPossibleSpaces($pId, $nbPlayers)");
        $allWorkers = Meeples::getAll();
        //self::dump("getPossibleSpaces($pId, $nbPlayers)  allWorkers",$allWorkers);
        //$nbAvailableWorkers = Meeples::countInLocation("reserve-$pId");
        $nbAvailableWorkers = $allWorkers->filter(function ($meeple) use ($pId){
                return $meeple['type'] == WORKER && $meeple['meeple_location'] == "reserve-$pId";
            })->count();

        $spaces = array(
            SPACE_FACTORY => $this->getPossibleSpacesInFactory($pId, $nbPlayers),
            SPACE_MINING => $this->getPossibleSpacesInMining($pId, $nbPlayers),
            SPACE_DELIVERY => $this->getPossibleSpacesInDelivery($pId),
            SPACE_BANK => $this->getPossibleSpacesInBank($pId, $nbPlayers),
            SPACE_ORDER => $this->getPossibleSpacesInOrder($pId, $nbPlayers),
        );
        $this->filterAvailableWorkers($spaces,$nbAvailableWorkers,$allWorkers);
        return $spaces;
    }

    /**
     * Update list of possible Worker Spaces to play according to $nbAvailableWorkers (number) and $allWorkers (collection)
     */
    function filterAvailableWorkers(&$spaces,$nbAvailableWorkers,$allWorkers){
        self::trace("filterAvailableWorkers($nbAvailableWorkers)");
        
        // FILTER on available meeples 
        $filter = function ($value ) use ($nbAvailableWorkers, $allWorkers) {
            $space = $value;
            if($space == SPACE_BANK_1) return true;//don't remove default space SPACE_BANK_1

            $workersAtWork = $allWorkers->filter(function ($meeple) use ($space){
                return $meeple['type'] == WORKER && $meeple['meeple_location'] == $space;
            })->count();
            if($nbAvailableWorkers < $workersAtWork + 1){
                //IMPOSSIBLE to add a worker => delete from array
                self::trace("filterAvailableWorkers($nbAvailableWorkers)... Impossible $space because $workersAtWork workers are there");
                return false;
            }
            return true;
        };
        foreach($spaces as $type => $typeSpaces){
            $spaces[$type] = array_values(array_filter($typeSpaces,$filter));
        }
    }
}
