<?php

namespace COAL\States;

use COAL\Helpers\Log;
use COAL\Managers\Players;
use COAL\Core\Notifications;
use COAL\Core\Globals;
use COAL\Managers\Cards;
use COAL\Managers\Tiles;

trait ConfirmUndoTrait
{
    public function addCheckpoint($state)
    {
        Globals::setChoices(0);
        Log::checkpoint($state);
    }

    public function addStep()
    {
        $stepId = Log::step($this->gamestate->state_id());
        Notifications::newUndoableStep(Players::getCurrent(), $stepId);
        Globals::incChoices();
    }

    public function argsConfirmTurn()
    {
        $data = [
            'previousSteps' => Log::getUndoableSteps(),
            'previousChoices' => Globals::getChoices(),
        ];
        return $data;
    }

    public function stConfirmTurn()
    {
        $player = Players::getActive();
        if (Globals::getChoices() == 0 || $player->getPref(OPTION_CONFIRM) == OPTION_CONFIRM_DISABLED) {
            $this->actConfirmTurn(true);
        }
    }

    public function actConfirmTurn($auto = false)
    {
        if (!$auto) {
            self::checkAction('actConfirmTurn');
        }
        Tiles::refillEmptyFactorySpaces(true);
        Cards::refillOtherOrderSpaces(null);

        $this->gamestate->nextState('confirm');
    }


    public function actRestart()
    {
        self::checkAction('actRestart');
        Log::undoTurn();
    }

    public function actUndoToStep($stepId)
    {
        self::checkAction('actRestart');
        Log::undoToStep($stepId);
    }
}
