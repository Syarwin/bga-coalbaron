<?php

namespace COAL\States;

use COAL\Helpers\Log;
use COAL\Managers\Players;
use COAL\Core\Notifications;
use COAL\Core\Globals;

trait ConfirmUndoTrait
{
    public function addCheckpoint($state)
    {
        Globals::setChoices(0);
        Log::checkpoint($state);
    }

    public function addStep()
    {
        Globals::incChoices();
        $stepId = Log::step($this->gamestate->state_id());
        Notifications::newUndoableStep(Players::getCurrent(), $stepId);
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
        if (Globals::getChoices() == 0) {
            $this->actConfirmTurn(true);
        }
    }

    public function actConfirmTurn($auto = false)
    {
        if (!$auto) {
            self::checkAction('actConfirmTurn');
        }
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
