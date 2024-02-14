<?php

namespace COAL\States;

use COAL\Core\Globals;
use COAL\Core\Stats;
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

    function argPlaceWorker()
    {
        $player = Players::getActive();
        $nbPlayers = Players::count();
        $spaces = $this->getPossibleSpaces($player->getId(), $nbPlayers, $player->getMoney());
        $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player);

        return array(
            'spaces' => $spaces,
            'nbrWorkersNeeded' => $this->getSpacesNeededWorkers($nbPlayers),
            'nbAvailableWorkers' => $nbAvailableWorkers,
        );
    }

    function actPlaceWorker($space)
    {
        self::checkAction('actPlaceWorker');
        $this->addStep();

        $player = Players::getActive();
        $nbPlayers = Players::count();

        // ANTICHEATS : available workers >0 + possible space
        $nbAvailableWorkers = Meeples::getNbAvailableWorkers($player);
        if ($nbAvailableWorkers == 0)
            throw new \BgaVisibleSystemException("Not enough workers to play");
        if (!$this->isPossibleSpace($player->getId(), $nbPlayers, $space, $player->getMoney(), $nbAvailableWorkers))
            throw new \BgaVisibleSystemException("Incorrect place to place a worker : $space");

        $this->placeWorker($player, $space);
        Stats::inc("nbActions", $player);

        if (ST_PLACE_WORKER == $this->gamestate->state_id()) {
            //GO TO NEXT STATE ONLY IF not already changed by the previous method
            $this->gamestate->nextState('next');
        }
    }

    /**
     * @param int $pId player id
     * @param int $nbPlayers number of players
     * @param string $space where to play
     * @param int $money money to spend
     * @param int $nbAvailableWorkers number of available workers
     * @return bool true if $space is possible to play, 
     *       false otherwise
     */
    function isPossibleSpace($pId, $nbPlayers, $space, $money, $nbAvailableWorkers)
    {
        self::trace("isPossibleSpace($pId, $nbPlayers,$space, $money,$nbAvailableWorkers)");

        switch ($space) {
            case SPACE_BANK_1:
            case SPACE_BANK_3:
            case SPACE_BANK_4:
            case SPACE_BANK_5:
            case SPACE_BANK_6:
                $possible = $this->isPossibleSpaceInBank($pId, $space, $nbPlayers);
                break;
            case SPACE_FACTORY_1:
            case SPACE_FACTORY_2:
            case SPACE_FACTORY_3:
            case SPACE_FACTORY_4:
            case SPACE_FACTORY_5:
            case SPACE_FACTORY_6:
            case SPACE_FACTORY_7:
            case SPACE_FACTORY_8:
            case SPACE_FACTORY_DRAW:
                $possible = $this->isPossibleSpaceInFactory($pId, $space, $money);
                break;
            case SPACE_ORDER_1:
            case SPACE_ORDER_2:
            case SPACE_ORDER_3:
            case SPACE_ORDER_4:
            case SPACE_ORDER_DRAW:
                $deckSize = Cards::countInLocation(CARD_LOCATION_DECK);
                $possible = $this->isPossibleSpaceInOrder($pId, $space, $deckSize);
                break;
            case SPACE_DELIVERY_BARROW:
            case SPACE_DELIVERY_CARRIAGE:
            case SPACE_DELIVERY_ENGINE:
            case SPACE_DELIVERY_MOTORCAR:
                $possible = $this->isPossibleSpaceInDelivery($pId, $space);
                break;
            case SPACE_MINING_4:
            case SPACE_MINING_6:
            case SPACE_MINING_8:
            case SPACE_MINING_10:
                $possible = $this->isPossibleSpaceInMining($pId, $space, $nbPlayers);
                break;
            default:
                $possible = false;
                break;
        }
        if (!$possible) return false;

        //filterAvailableWorkers
        $workersAtWork = Meeples::countWorkers($space);
        $nbrWorkersNeeded = $workersAtWork + 1;
        return self::hasEnoughWorkers($space, $nbAvailableWorkers, $nbrWorkersNeeded);
    }

    /**
     * List all possible Worker Spaces to play by player $pId
     */
    function getSpacesNeededWorkers($nbPlayers)
    {
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
        foreach ($spaces as $type => $typeSpaces) {
            foreach ($typeSpaces as $space) {
                if ($space == SPACE_BANK_1) {
                    $nbrWorkersNeeded = 1;
                } else {
                    $workersAtWork = $allWorkers->filter(function ($meeple) use ($space) {
                        return $meeple->getType() == WORKER && $meeple->getLocation() == $space;
                    })->count();
                    $nbrWorkersNeeded = $workersAtWork + 1;
                }
                $nbrNeededWorkers[$space] = $nbrWorkersNeeded;
            }
        }
        return $nbrNeededWorkers;
    }
    /**
     * List all possible Worker Spaces to play by player $pId
     * @param int $pId player id
     * @param int $nbPlayers number of players
     * @param int $money player money to spend
     */
    function getPossibleSpaces($pId, $nbPlayers, $money)
    {
        self::trace("getPossibleSpaces($pId, $nbPlayers, $money)");
        $nbrWorkersNeeded = $this->getSpacesNeededWorkers($nbPlayers);
        //$nbAvailableWorkers = Meeples::findAvailableWorkersInCollection($allWorkers,$pId)->count();
        $nbAvailableWorkers = Meeples::getNbAvailableWorkers($pId);

        $spaces = array(
            SPACE_FACTORY => $this->getPossibleSpacesInFactory($pId, $money),
            SPACE_MINING => $this->getPossibleSpacesInMining($pId, $nbPlayers),
            SPACE_DELIVERY => $this->getPossibleSpacesInDelivery($pId),
            SPACE_BANK => $this->getPossibleSpacesInBank($pId, $nbPlayers),
            SPACE_ORDER => $this->getPossibleSpacesInOrder($pId, $nbPlayers),
        );

        $this->filterAvailableWorkers($spaces, $nbAvailableWorkers, $nbrWorkersNeeded);
        return $spaces;
    }

    /**
     * @param string $space where to play
     * @param int $nbAvailableWorkers
     * @param int $nbrWorkersNeeded
     * @return bool true if player has enough workers to play, 
     *       false otherwise
     */
    static function hasEnoughWorkers($space, $nbAvailableWorkers, $nbrWorkersNeeded)
    {
        self::trace("hasEnoughWorkers($space,$nbAvailableWorkers,$nbrWorkersNeeded) ");
        if ($space == SPACE_BANK_1) return true; //don't remove default space SPACE_BANK_1

        if ($nbAvailableWorkers < $nbrWorkersNeeded) {
            self::trace("hasEnoughWorkers($nbAvailableWorkers)... Impossible $space because $nbrWorkersNeeded workers are needed");
            return false;
        }
        return true;
    }

    /**
     * Update list of possible Worker Spaces to play according to $nbAvailableWorkers (number) and $nbrWorkersNeeded (collection)
     */
    function filterAvailableWorkers(&$spaces, $nbAvailableWorkers, $nbrWorkersNeeded)
    {
        self::trace("filterAvailableWorkers($nbAvailableWorkers)");

        // FILTER on available meeples 
        $filter = function ($space) use ($nbAvailableWorkers, $nbrWorkersNeeded) {
            $nbrWorkersNeeded = $nbrWorkersNeeded[$space];
            return self::hasEnoughWorkers($space, $nbAvailableWorkers, $nbrWorkersNeeded);
        };
        foreach ($spaces as $type => $typeSpaces) {
            $spaces[$type] = array_values(array_filter($typeSpaces, $filter));
        }
    }

    function placeWorker($player, $space)
    {
        self::trace("placeWorker($space)...");
        switch ($space) {
            case SPACE_BANK_1:
            case SPACE_BANK_3:
            case SPACE_BANK_4:
            case SPACE_BANK_5:
            case SPACE_BANK_6:
                $this->placeWorkerInBank($player, $space);
                break;

            case SPACE_FACTORY_1:
            case SPACE_FACTORY_2:
            case SPACE_FACTORY_3:
            case SPACE_FACTORY_4:
            case SPACE_FACTORY_5:
            case SPACE_FACTORY_6:
            case SPACE_FACTORY_7:
            case SPACE_FACTORY_8:
                $this->placeWorkerInFactory($player, $space);
                break;
            case SPACE_FACTORY_DRAW:
                $this->placeWorkerInDrawFactory($player);
                break;
            case SPACE_ORDER_1:
            case SPACE_ORDER_2:
            case SPACE_ORDER_3:
            case SPACE_ORDER_4:
                $this->placeWorkerInOrder($player, $space);
                break;
            case SPACE_ORDER_DRAW:
                $this->placeWorkerInDrawOrder($player);
                break;
            case SPACE_DELIVERY_BARROW:
            case SPACE_DELIVERY_CARRIAGE:
            case SPACE_DELIVERY_ENGINE:
            case SPACE_DELIVERY_MOTORCAR:
                $this->placeWorkerInDelivery($player, $space);
                break;
            case SPACE_MINING_4:
            case SPACE_MINING_6:
            case SPACE_MINING_8:
            case SPACE_MINING_10:
                $this->placeWorkerInMining($player, $space);
                break;
            default:
                throw new \BgaVisibleSystemException("Not supported worker space : $space");
        }
    }
}
