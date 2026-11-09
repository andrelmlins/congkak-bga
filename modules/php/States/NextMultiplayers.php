<?php

declare(strict_types=1);

namespace Bga\Games\congkak\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\Games\congkak\Game;

/** After both players chose at the same time (docs/RULES.md, section 5): sows both houses and decides who plays next. */
class NextMultiplayers extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct(
            $game,
            id: 20,
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    function onEnteringState()
    {
        return $this->game->stepManager->nextMultiplayers();
    }
}
