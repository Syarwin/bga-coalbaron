<?php

namespace COAL\States;

use COAL\Core\Globals;
use COAL\Managers\Cards;
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

    function argPlaceWorker(){
        $player = Players::getActive();
        $nbPlayers = Players::count();
        $spaces = $this->getPossibleSpaces($player->getId(), $nbPlayers);
        $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player);
    
        return array(
          'spaces' => $spaces,
          'nbrWorkersNeeded' => $this->getSpacesNeededWorkers($nbPlayers),
          'nbAvailableWorkers' => $nbAvailableWorkers,
        );
    }
      
    function actPlaceWorker($space)
    {
        self::checkAction( 'actPlaceWorker' ); 

        $player = Players::getActive();
        $nbPlayers = Players::count();

        // ANTICHEATS : available workers >0 + possible space
        $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player);
        if($nbAvailableWorkers == 0) 
        throw new \BgaVisibleSystemException("Not enough workers to play");
        if(! $this->isPossibleSpace($player->getId(), $nbPlayers,$space) )
        throw new \BgaVisibleSystemException("Incorrect place to place a worker : $space");

        $this->placeWorker($player,$space);
        if( ST_PLACE_WORKER == $this->gamestate->state_id()){
        //GO TO NEXT STATE ONLY IF not already changed by the previous method
        $this->gamestate->nextState( 'next' );
        }
    }

    /**
     * @return bool true if $space is possible to play, 
     *       false otherwise
     */
    function isPossibleSpace($pId, $nbPlayers,$space) {
        $spaces = $this->getPossibleSpaces($pId, $nbPlayers);
        foreach($spaces as $type => $typeSpaces){
            foreach($typeSpaces as $possibleSpace){
                if($space == $possibleSpace){
                    self::trace("isPossibleSpace($pId, $nbPlayers,$space)... : OK $space in $type array");
                    return true;
                }
            }
            /* KO
            if(array_search($space,array_values($typeSpaces))){
                self::trace("isPossibleSpace($pId, $nbPlayers,$space)... : $space in $type array");
                return true;
            }
            */
            self::trace("isPossibleSpace($pId, $nbPlayers,$space)... : ko $space NOT in $type array");
            self::dump("isPossibleSpace($pId, $nbPlayers,$space)...",$typeSpaces);
            
        }
        return false;
    }

    /**
     * List all possible Worker Spaces to play by player $pId
     */
    function getSpacesNeededWorkers($nbPlayers) {
        self::trace("getSpacesNeededWorkers($nbPlayers)");
        $allWorkers = Meeples::getAll();
        $nbrNeededWorkers = array();

        $spaces = array(
            SPACE_FACTORY => $this->getAllSpacesInFactory($nbPlayers),
            SPACE_MINING => $this->getAllSpacesInMining($nbPlayers),
            SPACE_DELIVERY => $this->getAllSpacesInDelivery(),
            SPACE_BANK => $this->getAllSpacesInBank($nbPlayers),
            SPACE_ORDER => $this->getAllSpacesInOrder($nbPlayers),
        );
        foreach($spaces as $type => $typeSpaces){
            foreach($typeSpaces as $space){
                $workersAtWork = $allWorkers->filter(function ($meeple) use ($space){
                    return $meeple->getType() == WORKER && $meeple->getLocation() == $space;
                })->count();
                $nbrWorkersNeeded = $workersAtWork + 1;
                $nbrNeededWorkers[$space] = $nbrWorkersNeeded;
            }
        }
        return $nbrNeededWorkers;
    }
    /**
     * List all possible Worker Spaces to play by player $pId
     */
    function getPossibleSpaces($pId, $nbPlayers) {
        self::trace("getPossibleSpaces($pId, $nbPlayers)");
        $nbrWorkersNeeded = $this->getSpacesNeededWorkers($nbPlayers);
        //$nbAvailableWorkers = Meeples::findAvailableWorkersInCollection($allWorkers,$pId)->count();
        $nbAvailableWorkers = Meeples::getNbAvailableWorkers($pId);

        $spaces = array(
            SPACE_FACTORY => $this->getPossibleSpacesInFactory($pId),
            SPACE_MINING => $this->getPossibleSpacesInMining($pId, $nbPlayers),
            SPACE_DELIVERY => $this->getPossibleSpacesInDelivery($pId),
            SPACE_BANK => $this->getPossibleSpacesInBank($pId, $nbPlayers),
            SPACE_ORDER => $this->getPossibleSpacesInOrder($pId, $nbPlayers),
        );

        $this->filterAvailableWorkers($spaces,$nbAvailableWorkers,$nbrWorkersNeeded);
        return $spaces;
    }

    /**
     * Update list of possible Worker Spaces to play according to $nbAvailableWorkers (number) and $nbrWorkersNeeded (collection)
     */
    function filterAvailableWorkers(&$spaces,$nbAvailableWorkers,$nbrWorkersNeeded){
        self::trace("filterAvailableWorkers($nbAvailableWorkers)");
        
        // FILTER on available meeples 
        $filter = function ($value ) use ($nbAvailableWorkers, $nbrWorkersNeeded) {
            $space = $value;
            if($space == SPACE_BANK_1) return true;//don't remove default space SPACE_BANK_1

            $nbrWorkersNeeded = $nbrWorkersNeeded[$space];
            if($nbAvailableWorkers < $nbrWorkersNeeded){
                //IMPOSSIBLE to add a worker => delete from array
                self::trace("filterAvailableWorkers($nbAvailableWorkers)... Impossible $space because $nbrWorkersNeeded workers are needed");
                return false;
            }
            return true;
        };
        foreach($spaces as $type => $typeSpaces){
            $spaces[$type] = array_values(array_filter($typeSpaces,$filter));
        }
    }

    function placeWorker($player, $space){
        self::trace("placeWorker($space)...");
        switch($space)
        {
            case SPACE_BANK_1: 
            case SPACE_BANK_3: 
            case SPACE_BANK_4: 
            case SPACE_BANK_5: 
            case SPACE_BANK_6: 
                $this->placeWorkerInBank($player,$space);
                break;
                
            case SPACE_FACTORY_1: 
            case SPACE_FACTORY_2: 
            case SPACE_FACTORY_3: 
            case SPACE_FACTORY_4: 
            case SPACE_FACTORY_5: 
            case SPACE_FACTORY_6: 
            case SPACE_FACTORY_7: 
            case SPACE_FACTORY_8: 
                $this->placeWorkerInFactory($player,$space);
                break;
            case SPACE_FACTORY_DRAW: 
                $this->placeWorkerInDrawFactory($player);
                break;
            case SPACE_ORDER_1: 
            case SPACE_ORDER_2: 
            case SPACE_ORDER_3: 
            case SPACE_ORDER_4: 
                $this->placeWorkerInOrder($player,$space);
                break;
            case SPACE_ORDER_DRAW: 
                $this->placeWorkerInDrawOrder($player);
                break;
            case SPACE_DELIVERY_BARROW: 
            case SPACE_DELIVERY_CARRIAGE: 
            case SPACE_DELIVERY_ENGINE: 
            case SPACE_DELIVERY_MOTORCAR:
                    $this->placeWorkerInDelivery($player,$space);
                    break;
            case SPACE_MINING_4: 
            case SPACE_MINING_6: 
            case SPACE_MINING_8: 
            case SPACE_MINING_10: 
                $this->placeWorkerInMining($player,$space);
                break;
            default:
                throw new \BgaVisibleSystemException("Not supported worker space : $space");
        }
    }
}
