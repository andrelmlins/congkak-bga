<?php

declare(strict_types=1);

namespace Bga\Games\congkak\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\congkak\Game;

/** After a turn ends (docs/RULES.md, section 6): a new round when a side has no seeds, otherwise the other player. */
class NextPlayer extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 21,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    function onEnteringState()
    {
        return $this->game->stepManager->nextPlayer();
    }
}
