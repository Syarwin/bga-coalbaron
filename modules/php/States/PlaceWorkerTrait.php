<?php

namespace COAL\States;

use COAL\Core\Game;
use COAL\Core\Globals;
use COAL\Core\Notifications;
use COAL\Core\Engine;
use COAL\Core\Stats;
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
        return array(
            SPACE_FACTORY => $this->getPossibleSpacesInFactory($pId, $nbPlayers),
            SPACE_MINING => $this->getPossibleSpacesInMining($pId, $nbPlayers),
            SPACE_DELIVERY => $this->getPossibleSpacesInDelivery($pId),
            SPACE_BANK => $this->getPossibleSpacesInBank($pId, $nbPlayers),
            SPACE_ORDER => $this->getPossibleSpacesInOrder($pId, $nbPlayers),
        );
    }
}
