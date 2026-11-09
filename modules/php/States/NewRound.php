<?php

declare(strict_types=1);

namespace Bga\Games\congkak\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\congkak\Game;

/**
 * The start of the game and the end of every round (docs/RULES.md, section 8): closes the round, reseeds the houses and says who opens the next one,
 * or ends the game.
 */
class NewRound extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 2,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    function onEnteringState()
    {
        return $this->game->stepManager->newRound();
    }
}
